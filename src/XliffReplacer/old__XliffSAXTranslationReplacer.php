<?php

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\Utils\Strings;

class oldXliffSAXTranslationReplacer extends AbstractXliffReplacer {
    /**
     * @var int
     */
    private $mdaGroupCounter = 0;

    /**
     * @var array
     */
    private $nodesToCopy = [
            'source',
            'mda:metadata',
            'memsource:additionalTagData',
            'originalData',
            'seg-source',
            'value',
            'bpt',
            'ept',
            'ph',
            'st',
            'note',
            'context',
            'context-group'
    ];

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

            // `translate` attribute can be only yes or no
            if ( isset( $attr[ 'translate' ] ) && $attr[ 'translate' ] === 'no' ) {
                $attr[ 'translate' ] = 'no';
            } else {
                $attr[ 'translate' ] = 'yes';
            }

            // current 'translate' attribute of the current trans-unit
            $this->currentTransUnitIsTranslatable = $attr[ 'translate' ];
        }

        if ( 'source' === $name ) {
            $this->sourceAttributes = $attr;
        }

        if ( 'mda:metadata' === $name ) {
            $this->unitContainsMda = true;
        }

        // check if we are entering into a <target>
        if ( 'target' === $name ) {

            if ( $this->currentTransUnitIsTranslatable === 'no' ) {
                $this->inTarget = false;
            } else {
                $this->inTarget = true;
            }
        }

        // check if we are inside a <target>, obviously this happen only if there are targets inside the trans-unit
        // <target> must be stripped to be replaced, so this check avoids <target> reconstruction
        if ( !$this->inTarget ) {

            $tag = '';

            //
            // ============================================
            // only for Xliff 2.*
            // ============================================
            //
            // In xliff v2 we MUST add <mda:metadata> BEFORE <notes>/<originalData>/<segment>/<ignorable>
            //
            // As documentation says, <unit> contains:
            //
            // - elements from other namespaces, OPTIONAL
            // - Zero or one <notes> elements followed by
            // - Zero or one <originalData> element followed by
            // - One or more <segment> or <ignorable> elements in any order.
            //
            // For more info please refer to:
            //
            // http://docs.oasis-open.org/xliff/xliff-core/v2.0/os/xliff-core-v2.0-os.html#unit
            //
            if ( $this->xliffVersion === 2 && ( $name === 'notes' || $name === 'originalData' || $name === 'segment' || $name === 'ignorable' ) && $this->unitContainsMda === false ) {
                if ( isset( $this->transUnits[ $this->currentTransUnitId ] ) && !empty( $this->transUnits[ $this->currentTransUnitId ] ) && !$this->hasWrittenCounts ) {

                    // we need to update counts here
                    $this->updateCounts();
                    $this->hasWrittenCounts = true;

                    $tag                   .= $this->getWordCountGroupForXliffV2();
                    $this->unitContainsMda = true;
                }
            }

            // construct tag
            $tag .= "<$name ";

            $stateProp = null;

            foreach ( $attr as $k => $v ) {

                //if tag name is file, we must replace the target-language attribute
                if ( $name === 'file' && $k === 'target-language' && !empty( $this->targetLang ) ) {
                    //replace Target language with job language provided from constructor
                    $tag .= "$k=\"$this->targetLang\" ";
                } else {
                    $pos = 0;
                    if ( $this->currentTransUnitId and isset( $this->transUnits[ $this->currentTransUnitId ] ) ) {
                        $pos = current( $this->transUnits[ $this->currentTransUnitId ] );
                    }

                    if ( $name === $this->tuTagName and isset( $this->segments[ $pos ] ) and isset( $this->segments[ $pos ][ 'sid' ] ) ) {

                        $sid = $this->segments[ $pos ][ 'sid' ];

                        // add `help-id` to xliff v.1*
                        // add `mtc:segment-id` to xliff v.2*
                        if ( $this->xliffVersion === 1 && strpos( $tag, 'help-id' ) === false ) {
                            if ( !empty( $sid ) ) {
                                $tag .= "help-id=\"$sid\" ";
                            }
                        } elseif ( $this->xliffVersion === 2 && strpos( $tag, 'mtc:segment-id' ) === false ) {
                            if ( !empty( $sid ) ) {
                                $tag .= "mtc:segment-id=\"$sid\" ";
                            }
                        }

                    } elseif ( 'segment' === $name && $this->xliffVersion === 2 ) { // add state to segment in Xliff v2
                        [ $stateProp, ] = StatusToStateAttribute::getState( $this->segments[ $pos ][ 'status' ], $this->xliffVersion );
                    }

                    //normal tag flux, put attributes in it but skip for translation state and set the right value for the attribute
                    if ( $k != 'state' ) {
                        $tag .= "$k=\"$v\" ";
                    }

                }

            }

            // replace state for xliff v2
            if ( $stateProp ) {
                $tag .= $stateProp;
            }

            // add oasis xliff 20 namespace
            if ( $this->xliffVersion === 2 && $name === 'xliff' && !array_key_exists( 'xmlns:mda', $attr ) ) {
                $tag .= 'xmlns:mda="urn:oasis:names:tc:xliff:metadata:2.0"';
            }

            // add MateCat specific namespace, we want maybe add non-XLIFF attributes
            if ( $name === 'xliff' && !array_key_exists( 'xmlns:mtc', $attr ) ) {
                $tag .= ' xmlns:mtc="https://www.matecat.com" ';
            }

            // trgLang
            if ( $name === 'xliff' ) {
                $tag = preg_replace( '/trgLang="(.*?)"/', 'trgLang="' . $this->targetLang . '"', $tag );
            }

            $lastChar = $this->getLastCharacter( $parser );

            //trim last space
            $tag = rtrim( $tag );

            //detect empty tag
            $this->isEmpty = ( $lastChar == '/' || $name == 'x' );
            if ( $this->isEmpty ) {
                $tag .= '/';
            }

            //add tag ending
            $tag .= ">";

            //set a a Buffer for the segSource Source tag
            if ( $this->bufferIsActive || in_array( $name, $this->nodesToCopy ) ) { // we are opening a critical CDATA section

                //WARNING BECAUSE SOURCE AND SEG-SOURCE TAGS CAN BE EMPTY IN SOME CASES!!!!!
                //so check for isEmpty also in conjunction with name
                if ( $this->isEmpty && ( 'source' === $name || 'seg-source' === $name ) ) {
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

        // update segmentPositionInTu

        if ( $this->xliffVersion === 1 && $this->inTU && $name === 'source' ) {
            $this->segmentPositionInTu++;
        }

        if ( $this->xliffVersion === 2 && $this->inTU && $name === 'segment' ) {
            $this->segmentPositionInTu++;
        }
    }

    /**
     * @inheritDoc
     */
    protected function tagClose( $parser, $name ) {
        $tag = '';

        /**
         * if is a tag within <target> or
         * if it is an empty tag, do not add closing tag because we have already closed it in
         *
         * self::tagOpen method
         */
        if ( !$this->isEmpty ) {

            if ( !$this->inTarget ) {
                $tag = "</$name>";
            }

            if ( 'target' == $name ) {

                if ( $this->currentTransUnitIsTranslatable === 'no' ) {
                    // do nothing
                } elseif ( isset( $this->transUnits[ $this->currentTransUnitId ] ) ) {

                    // get translation of current segment, by indirect indexing: id -> positional index -> segment
                    // actually there may be more that one segment to that ID if there are two mrk of the same source segment

                    $listOfSegmentsIds = $this->transUnits[ $this->currentTransUnitId ];

                    // $currentSegmentId
                    if ( !empty( $listOfSegmentsIds ) ) {
                        $this->setCurrentSegmentArray( $listOfSegmentsIds );
                    }

                    /*
                     * At the end of every cycle the segment grouping information is lost: unset( 'matecat|' . $this->currentId )
                     *
                     * We need to take the info about the last segment parsed
                     *          ( normally more than 1 db row because of mrk tags )
                     *
                     * So, copy the current segment data group to an another structure to take the last one segment
                     * for the next tagOpen ( possible sdl:seg-defs )
                     *
                     */

                    $this->lastTransUnit = [];

                    $last_value    = null;
                    $segmentsCount = count( $listOfSegmentsIds );
                    for ( $i = 0; $i < $segmentsCount; $i++ ) {
                        $id = $listOfSegmentsIds[ $i ];
                        if ( isset( $this->segments[ $id ] ) && ( $i == 0 || $last_value + 1 == $listOfSegmentsIds[ $i ] ) ) {
                            $last_value            = $listOfSegmentsIds[ $i ];
                            $this->lastTransUnit[] = $this->segments[ $id ];
                        }
                    }

                    // init translation and state
                    $translation  = '';
                    $lastMrkState = null;
                    $stateProp    = '';

                    // we must reset the lastMrkId found because this is a new segment.
                    $lastMrkId = -1;

                    if ( $this->xliffVersion === 2 ) {
                        $seg = $this->segments[ $this->currentSegmentArray[ 'sid' ] ];

                        // update counts
                        if ( !$this->hasWrittenCounts && !empty( $seg ) ) {
                            $this->updateSegmentCounts( $seg );
                        }

                        // delete translations so the prepareSegment
                        // will put source content in target tag
                        if ( $this->sourceInTarget ) {
                            $seg[ 'translation' ] = '';
                            $this->resetCounts();
                        }

                        // append $translation
                        $translation = $this->prepareTranslation( $seg, $translation );

                        [ $stateProp, ] = StatusToStateAttribute::getState( $seg[ 'status' ], $this->xliffVersion );

                    } else {
                        foreach ( $listOfSegmentsIds as $pos => $id ) {

                            /*
                             * This routine works to respect the positional orders of markers.
                             * In every cycle we check if the mrk of the segment is below or equal the last one.
                             * When this is true, means that the mrk id belongs to the next segment with the same internal_id
                             * so we MUST stop to apply markers and translations
                             * and stop to add eq_word_count
                             *
                             * Begin:
                             * pre-assign zero to the new mrk if this is the first one ( in this segment )
                             * If it is null leave it NULL
                             */
                            if ( (int)$this->segments[ $id ][ "mrk_id" ] < 0 && $this->segments[ $id ][ "mrk_id" ] !== null ) {
                                $this->segments[ $id ][ "mrk_id" ] = 0;
                            }

                            /*
                             * WARNING:
                             * For those seg-source that doesn't have a mrk ( having a mrk id === null )
                             * ( null <= -1 ) === true
                             * so, cast to int
                             */
                            if ( (int)$this->segments[ $id ][ "mrk_id" ] <= $lastMrkId ) {
                                break;
                            }

                            // set $this->currentSegment
                            $seg = $this->segments[ $id ];

                            // update counts
                            if ( !empty( $seg ) ) {
                                $this->updateSegmentCounts( $seg );
                            }

                            // delete translations so the prepareSegment
                            // will put source content in target tag
                            if ( $this->sourceInTarget ) {
                                $seg[ 'translation' ] = '';
                                $this->resetCounts();
                            }

                            // append $translation
                            $translation = $this->prepareTranslation( $seg, $translation );

                            // for xliff 2 we need $this->transUnits[ $this->currentId ] [ $pos ] for populating metadata

                            unset( $this->transUnits[ $this->currentTransUnitId ] [ $pos ] );

                            $lastMrkId = $this->segments[ $id ][ "mrk_id" ];

                            [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( $seg[ 'status' ], $this->xliffVersion, $stateProp, $lastMrkState );

                        }
                    }

                    //append translation
                    $tag = $this->createTargetTag( $translation, $stateProp );

                }

                // signal we are leaving a target
                $this->targetWasWritten = true;
                $this->inTarget         = false;
                $this->postProcAndFlush( $this->outputFP, $tag, true );
            } elseif ( in_array( $name, $this->nodesToCopy ) && !$this->inTarget ) { // we are closing a critical CDATA section

                $this->bufferIsActive = false;

                // only for Xliff 2.*
                // write here <mda:metaGroup> and <mda:meta> if already present in the <unit>
                if ( 'mda:metadata' === $name && $this->unitContainsMda && $this->xliffVersion === 2 && !$this->hasWrittenCounts ) {

                    // we need to update counts here
                    $this->updateCounts();
                    $this->hasWrittenCounts = true;

                    $tag = $this->CDATABuffer;
                    $tag .= $this->getWordCountGroupForXliffV2( false );
                    $tag .= "    </mda:metadata>";

                } else {
                    $tag = $this->CDATABuffer . "</$name>";
                }

                $this->CDATABuffer = "";

                //flush to pointer
                $this->postProcAndFlush( $this->outputFP, $tag );
            } elseif ( 'segment' === $name ) {

                // only for Xliff 2.*
                // if segment has no <target> add it BEFORE </segment>
                if ( !$this->targetWasWritten ) {

                    $seg = $this->getCurrentSegment();

                    if ( isset( $seg[ 'translation' ] ) ) {

                        $translation = $this->prepareTranslation( $seg );
                        [ $stateProp, ] = StatusToStateAttribute::getState( $seg[ 'status' ], $this->xliffVersion );

                        // replace the tag
                        $tag = $this->createTargetTag( $translation, $stateProp );

                        $tag .= '</segment>';

                    }

                }

                $this->postProcAndFlush( $this->outputFP, $tag );

                // we are leaving <segment>, reset $segmentHasTarget
                $this->targetWasWritten = false;

            } elseif ( $name === 'trans-unit' ) {

                // only for Xliff 1.*
                // handling </trans-unit> closure
                if ( !$this->targetWasWritten ) {

                    $seg = $this->getCurrentSegment();

                    if ( isset( $seg[ 'translation' ] ) ) {
                        $translation = $this->prepareTranslation( $seg );
                        [ $stateProp, ] = StatusToStateAttribute::getState( $seg[ 'status' ], $this->xliffVersion );

                        // replace the tag
                        $tag = $this->createTargetTag( $translation, $stateProp );
                        $tag .= '</trans-unit>';

                    }

                    $this->postProcAndFlush( $this->outputFP, $tag );

                } else {
                    $this->postProcAndFlush( $this->outputFP, '</trans-unit>' );
                    $this->targetWasWritten = false;
                }


            } elseif ( $this->bufferIsActive ) { // this is a tag ( <g | <mrk ) inside a seg or seg-source tag
                $this->CDATABuffer .= "</$name>";
                // Do NOT Flush
            } else { //generic tag closure do Nothing
                // flush to pointer
                $this->postProcAndFlush( $this->outputFP, $tag );
            }
        } elseif ( $this->CDATABuffer === '<note/>' && $this->bufferIsActive === true ) {
            $this->postProcAndFlush( $this->outputFP, '<note/>' );
            $this->bufferIsActive = false;
            $this->CDATABuffer    = '';
            $this->isEmpty        = false;
        } else {
            //ok, nothing to be done; reset flag for next coming tag
            $this->isEmpty = false;
        }

        // check if we are leaving a <trans-unit> (xliff v1.*) or <unit> (xliff v2.*)
        if ( $this->tuTagName === $name ) {
            $this->currentTransUnitIsTranslatable = null;
            $this->inTU                           = false;
            $this->segmentPositionInTu       = -1;
            $this->unitContainsMda           = false;
            $this->hasWrittenCounts          = false;
            $this->sourceAttributes          = [];

            $this->resetCounts();
        }
    }

    /**
     * Set the current segment array (with segment id and trans-unit id)
     *
     * @param array $listOfSegmentsIds
     */
    private function setCurrentSegmentArray( array $listOfSegmentsIds = [] ) {
        // $currentSegmentId
        if ( empty( $this->currentSegmentArray ) ) {
            $this->currentSegmentArray = [
                    'sid' => $listOfSegmentsIds[ 0 ],
                    'tid' => $this->currentTransUnitId,
            ];
        } else {
            if ( $this->currentSegmentArray[ 'tid' ] === $this->currentTransUnitId ) {
                $key                                = array_search( $this->currentSegmentArray[ 'sid' ], $listOfSegmentsIds );
                $this->currentSegmentArray[ 'sid' ] = $listOfSegmentsIds[ $key + 1 ];
            } else {
                $this->currentSegmentArray = [
                        'sid' => $listOfSegmentsIds[ 0 ],
                        'tid' => $this->currentTransUnitId,
                ];
            }
        }
    }

    /**
     * Update counts
     */
    private function updateCounts() {
        // populate counts
        $listOfSegmentsIds = $this->transUnits[ $this->currentTransUnitId ];

        // $currentSegmentId
        if ( !empty( $listOfSegmentsIds ) ) {
            $this->setCurrentSegmentArray( $listOfSegmentsIds );
        }

        if ( $this->xliffVersion === 2 ) {
            $seg = $this->segments[ $this->currentSegmentArray[ 'sid' ] ];
            if ( !empty( $seg ) ) {
                $this->updateSegmentCounts( $seg );
            }
        } else {
            foreach ( $listOfSegmentsIds as $pos => $id ) {
                $seg = $this->segments[ $id ];
                if ( !empty( $seg ) ) {
                    $this->updateSegmentCounts( $seg );
                }
            }
        }

        $this->currentSegmentArray = [];
    }

    /**
     * @param array $seg
     */
    private function updateSegmentCounts( array $seg = [] ) {

        $raw_word_count = $seg[ 'raw_word_count' ];
        $eq_word_count  = ( floor( $seg[ 'eq_word_count' ] * 100 ) / 100 );

        $this->counts[ 'segments_count_array' ][ $seg[ 'sid' ] ] = [
                'raw_word_count' => $raw_word_count,
                'eq_word_count'  => $eq_word_count,
        ];

        $this->counts[ 'raw_word_count' ] += $raw_word_count;
        $this->counts[ 'eq_word_count' ]  += $eq_word_count;
    }

    private function resetCounts() {
        $this->counts[ 'segments_count_array' ] = [];
        $this->counts[ 'raw_word_count' ]       = 0;
        $this->counts[ 'eq_word_count' ]        = 0;
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
        $dataRefMap  = ( isset( $seg[ 'data_ref_map' ] ) ) ? Strings::jsonToArray( $seg[ 'data_ref_map' ] ) : [];

        if ( $seg [ 'translation' ] == '' ) {
            $translation = $segment;
        } else {
            if ( $this->callback instanceof XliffReplacerCallbackInterface ) {
                $error = ( !empty( $seg[ 'error' ] ) ) ? $seg[ 'error' ] : null;
                if ( $this->callback->thereAreErrors( $seg[ 'sid' ], $segment, $translation, $dataRefMap, $error ) ) {
                    $translation = '|||UNTRANSLATED_CONTENT_START|||' . $segment . '|||UNTRANSLATED_CONTENT_END|||';
                }
            }
        }

        // for xliff v2 we ignore the marks on purpose
        if ( $this->xliffVersion === 2 ) {
            return $translation;
        }

        if ( $seg[ 'mrk_id' ] !== null && $seg[ 'mrk_id' ] != '' ) {
            if ( $this->targetLang === 'ja-JP' ) {
                $seg[ 'mrk_succ_tags' ] = ltrim( $seg[ 'mrk_succ_tags' ] );
            }

            $translation = "<mrk mid=\"" . $seg[ 'mrk_id' ] . "\" mtype=\"seg\">" . $seg[ 'mrk_prev_tags' ] . $translation . $seg[ 'mrk_succ_tags' ] . "</mrk>";
        }

        $transUnitTranslation .= $seg[ 'prev_tags' ] . $translation . $endTags . $seg[ 'succ_tags' ];

        return $transUnitTranslation;
    }


    /**
     * @param $raw_word_count
     * @param $eq_word_count
     *
     * @return string
     */
    private function getWordCountGroup( $raw_word_count, $eq_word_count ) {
        return "\n<count-group name=\"$this->currentTransUnitId\"><count count-type=\"x-matecat-raw\">$raw_word_count</count><count count-type=\"x-matecat-weighted\">$eq_word_count</count></count-group>";
    }

    /**
     * @return array
     */
    private function getCurrentSegment() {
        if ( $this->currentTransUnitIsTranslatable === 'yes' && isset( $this->transUnits[ $this->currentTransUnitId ] ) ) {
            $index = $this->transUnits[ $this->currentTransUnitId ][ $this->segmentPositionInTu ];

            if ( isset( $this->segments[ $index ] ) ) {
                return $this->segments[ $index ];
            }
        }

        return [];
    }

    /**
     * This function creates a <target>
     *
     * @param $translation
     * @param $stateProp
     *
     * @return string
     */
    private function createTargetTag( $translation, $stateProp ) {

        $targetLang = '';
        if ( $this->xliffVersion === 1 ) {
            $targetLang = ' xml:lang="' . $this->targetLang . '"';
        }

        switch ( $this->xliffVersion ) {
            case 1:
            default:
                $tag = "<target $targetLang $stateProp>$translation</target>";

                // if it's a Trados file don't append count group
                if ( get_class( $this ) !== oldSdlOldXliffSAXTranslationReplacer::class ) {
                    $tag .= $this->getWordCountGroup( $this->counts[ 'raw_word_count' ], $this->counts[ 'eq_word_count' ] );
                }

                return $tag;

            case 2:
                return "<target>$translation</target>";
        }

    }

    /**
     * @param bool $withMetadataTag
     *
     * @return string
     */
    private function getWordCountGroupForXliffV2( $withMetadataTag = true ) {

        $this->mdaGroupCounter++;
        $segments_count_array = $this->counts[ 'segments_count_array' ];

        $id = $this->currentSegmentArray;


        $return = '';

        if ( $withMetadataTag === true ) {
            $return .= '<mda:metadata>';
        }

        $index = 0;
        foreach ( $segments_count_array as $segments_count_item ) {

            $id = 'word_count_tu[' . $this->currentTransUnitId . '][' . $index . ']';
            $index++;

            $return .= "    <mda:metaGroup id=\"" . $id . "\" category=\"row_xml_attribute\">
                                <mda:meta type=\"x-matecat-raw\">" . $segments_count_item[ 'raw_word_count' ] . "</mda:meta>
                                <mda:meta type=\"x-matecat-weighted\">" . $segments_count_item[ 'eq_word_count' ] . "</mda:meta>
                            </mda:metaGroup>";
        }

        if ( $withMetadataTag === true ) {
            $return .= '</mda:metadata>';
        }

        return $return;

    }

}
