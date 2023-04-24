<?php

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Utils\Strings;

class SdlXliffSAXTranslationReplacer extends XliffSAXTranslationReplacer {
    protected $markerPos = "";

    /**
     * @inheritDoc
     */
    protected function tagOpen( $parser, $name, $attr ) {
        // check if we are entering into a <trans-unit> (xliff v1.*) or <unit> (xliff v2.*)
        if ( $this->tuTagName === $name ) {
            $this->inTU = true;

            // get id
            // trim to first 100 characters because this is the limit on Matecat's DB
            $this->currentTransUnitId = substr( $attr[ 'id' ], 0, 100 );

            // current 'translate' attribute of the current trans-unit
            $this->currentTransUnitTranslate = isset( $attr[ 'translate' ] ) ? $attr[ 'translate' ] : 'yes';
        }

        // check if we are entering into a <target>
        if ( 'target' == $name ) {
            if ( $this->currentTransUnitTranslate === 'no' ) {
                $this->inTarget = false;
            } else {
                $this->inTarget = true;
            }
        }

        // reset Marker positions
        if ( 'sdl:seg-defs' == $name ) {
            $this->markerPos = 0;
        }

        // check if we are inside a <target>, obviously this happen only if there are targets inside the trans-unit
        // <target> must be stripped to be replaced, so this check avoids <target> reconstruction
        if ( !$this->inTarget ) {

            // costruct tag
            $tag = "<$name ";

            // needed to avoid multiple conf writing inside the same tag
            // because the "conf" attribute could be not present in the tag,
            // so the check on it's name is not enough
            $_sdlStatus_confWritten = false;

            foreach ( $attr as $k => $v ) {

                // if tag name is file, we must replace the target-language attribute
                if ( $name == 'file' && $k == 'target-language' && !empty( $this->targetLang ) ) {
                    //replace Target language with job language provided from constructor
                    $tag .= "$k=\"$this->targetLang\" ";

                    if ( null !== $this->logger ) {
                        $this->logger->debug( $k . " => " . $this->targetLang );
                    }
                } elseif ( 'sdl:seg' == $name ) {

                    // write the confidence level for this segment ( Translated, Draft, etc. )
                    if ( isset( $this->segments[ 'matecat|' . $this->currentTransUnitId ] ) && $_sdlStatus_confWritten === false ) {

                        // append definition attribute
                        $tag .= $this->prepareTargetStatuses( $this->lastTransUnit[ $this->markerPos ] );

                        //prepare for an eventual next cycle
                        $this->markerPos++;
                        $_sdlStatus_confWritten = true;
                    }

                    // Warning, this is NOT an elseif
                    if ( $k != 'conf' ) {
                        //put also the current attribute in it if it is not a "conf" attribute
                        $tag .= "$k=\"$v\" ";
                    }
                } else {
                    //normal tag flux, put attributes in it
                    $tag .= "$k=\"$v\" ";
                }
            }

            // this logic helps detecting empty tags
            // get current position of SAX pointer in all the stream of data is has read so far:
            // it points at the end of current tag
            $idx = xml_get_current_byte_index( $parser );

            // check whether the bounds of current tag are entirely in current buffer || the end of the current tag
            // is outside current buffer (in the latter case, it's in next buffer to be read by the while loop);
            // this check is necessary because we may have truncated a tag in half with current read,
            // and the other half may be encountered in the next buffer it will be passed
            if ( isset( $this->currentBuffer[ $idx - $this->offset ] ) ) {
                // if this tag entire lenght fitted in the buffer, the last char must be the last
                // symbol before the '>'; if it's an empty tag, it is assumed that it's a '/'
                $tmp_offset = $idx - $this->offset;
                $lastChar   = $this->currentBuffer[ $tmp_offset ];
            } else {
                //if it's out, simple use the last character of the chunk
                $tmp_offset = $this->len - 1;
                $lastChar   = $this->currentBuffer[ $tmp_offset ];
            }

            // trim last space
            $tag = rtrim( $tag );

            // detect empty tag
            $this->isEmpty = ( $lastChar == '/' || $name == 'x' );
            if ( $this->isEmpty ) {
                $tag .= '/';
            }

            // add tag ending
            $tag .= ">";

            // set a a Buffer for the segSource Source tag
            if ( 'source' == $name
                    || 'seg-source' === $name
                    || $this->bufferIsActive
                    || 'value' === $name
                    || 'bpt' === $name
                    || 'ept' === $name
                    || 'ph' === $name
                    || 'st' === $name
                    || 'note' === $name
                    || 'context' === $name ) { // we are opening a critical CDATA section

                // WARNING BECAUSE SOURCE AND SEG-SOURCE TAGS CAN BE EMPTY IN SOME CASES!!!!!
                // so check for isEmpty also in conjunction with name
                if ( $this->isEmpty && ( 'source' == $name || 'seg-source' == $name ) ) {
                    $this->postProcAndFlush( $this->outputFP, $tag );
                } else {
                    //these are NOT source/seg-source/value empty tags, THERE IS A CONTENT, write it in buffer
                    $this->bufferIsActive = true;
                    $this->CDATABuffer    .= $tag;
                }
            } else {
                $this->postProcAndFlush( $this->outputFP, $tag );
            }
        }
    }

    /**
     * prepare segment tagging for xliff insertion
     *
     * @param array  $seg
     * @param string $transUnitTranslation
     *
     * @return string
     */
    protected function prepareTranslation( $seg, $transUnitTranslation = "" ) {
        $endTags = "";

        $segment     = Strings::removeDangerousChars( $seg [ 'segment' ] );
        $translation = Strings::removeDangerousChars( $seg [ 'translation' ] );
        $dataRefMap  = ( isset( $seg[ 'data_ref_map' ] ) && $seg[ 'data_ref_map' ] !== null ) ? Strings::jsonToArray( $seg[ 'data_ref_map' ] ) : [];

        if ( is_null( $seg [ 'translation' ] ) || $seg [ 'translation' ] == '' ) {
            $translation = $segment;
        } else {
            if ( $this->callback ) {
                $error = (isset($seg['error'])) ? $seg['error'] : null;
                if ( $this->callback->thereAreErrors( $seg[ 'sid' ], $segment, $translation, $dataRefMap, $error ) ) {
                    $translation = '|||UNTRANSLATED_CONTENT_START|||' . $segment . '|||UNTRANSLATED_CONTENT_END|||';
                }
            }
        }

        // for Trados the trailing spaces after </mrk> are meaningful
        // so we trim the translation from Matecat DB and add them after </mrk>
        $trailingSpaces = '';
        for ( $s = 0; $s < Strings::getTheNumberOfTrailingSpaces( $translation ); $s++ ) {
            $trailingSpaces .= ' ';
        }

        if ( $seg[ 'mrk_id' ] !== null && $seg[ 'mrk_id' ] != '' ) {
            if ( $this->targetLang === 'ja-JP' ) {
                $seg[ 'mrk_succ_tags' ] = ltrim( $seg[ 'mrk_succ_tags' ] );
            }

            $translation = "<mrk mid=\"" . $seg[ 'mrk_id' ] . "\" mtype=\"seg\">" . $seg[ 'mrk_prev_tags' ] . rtrim( $translation ) . $seg[ 'mrk_succ_tags' ] . "</mrk>" . $trailingSpaces;
        }

        // we need to trim succ_tags here because we already added the trailing spaces after </mrk>
        $transUnitTranslation .= $seg[ 'prev_tags' ] . $translation . $endTags . ltrim( $seg[ 'succ_tags' ] );

        return $transUnitTranslation;
    }

    /**
     * @param $segment
     *
     * @return string
     */
    protected function prepareTargetStatuses( $segment ) {
        $statusMap = [
                'NEW'        => '',
                'DRAFT'      => 'Draft',
                'TRANSLATED' => 'Translated',
                'APPROVED'   => 'ApprovedTranslation',
                'REJECTED'   => 'RejectedTranslation',
        ];

        return "conf=\"{$statusMap[ $segment[ 'status' ] ]}\" ";
    }

    /**
     * @param $seg
     * @param $state_prop
     * @param $lastMrkState
     *
     * @return array
     */
    protected function setTransUnitState( $seg, $state_prop, $lastMrkState ) {
        return [ null, null ];
    }

    /**
     * @param $raw_word_count
     * @param $eq_word_count
     *
     * @return string
     */
    protected function getWordCountGroup( $raw_word_count, $eq_word_count ) {
        return '';
    }
}
