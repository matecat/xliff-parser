<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 02/08/24
 * Time: 11:45
 *
 */

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Utils\Strings;

class Xliff12 extends AbstractXliffReplacer {

    /**
     * @var array
     */
    protected array $nodesToBuffer = [
            'source',
            'seg-source',
            'note',
            'context-group'
    ];

    /**
     * @var string
     */
    protected string $tuTagName = 'trans-unit';

    /**
     * @var string
     */
    protected string $alternativeMatchesTag = 'alt-trans';

    /**
     * @var string
     */
    protected string $namespace             = "mtc";       // Custom namespace

    /**
     * @inheritDoc
     */
    protected function tagOpen( $parser, string $name, array $attr ) {

        $this->handleOpenUnit( $name, $attr );

        $this->trySetAltTrans( $name );;
        $this->checkSetInTarget( $name );

        // open buffer
        $this->setInBuffer( $name );

        // check if we are inside a <target>, obviously this happen only if there are targets inside the trans-unit
        // <target> must be stripped to be replaced, so this check avoids <target> reconstruction
        if ( !$this->inTarget ) {

            $tag = '';

            // construct tag
            $tag .= "<$name ";

            foreach ( $attr as $k => $v ) {

                //if tag name is file, we must replace the target-language attribute
                if ( $name === 'file' && $k === 'target-language' && !empty( $this->targetLang ) ) {
                    //replace Target language with job language provided from constructor
                    $tag .= "$k=\"$this->targetLang\" ";
                } else {
                    $tag .= "$k=\"$v\" ";
                }

            }

            $seg = $this->getCurrentSegment();

            if ( $name === $this->tuTagName && !empty( $seg ) && isset( $seg[ 'sid' ] ) ) {

                // add `help-id` to xliff v.1*
                if ( strpos( $tag, 'help-id' ) === false ) {
                    if ( !empty( $seg[ 'sid' ] ) ) {
                        $tag .= "help-id=\"{$seg[ 'sid' ]}\" ";
                    }
                }

            }

            $tag = $this->handleOpenXliffTag( $name, $attr, $tag );

            $this->checkForSelfClosedTagAndFlush( $parser, $tag );

        }

    }


    /**
     * @inheritDoc
     */
    protected function tagClose( $parser, string $name ) {
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

            if ( 'target' == $name && !$this->inAltTrans ) {

                if ( isset( $this->transUnits[ $this->currentTransUnitId ] ) ) {

                    // get translation of current segment, by indirect indexing: id -> positional index -> segment
                    // actually there may be more than one segment to that ID if there are two mrk of the same source segment
                    $tag = $this->rebuildTarget();

                }

                $this->targetWasWritten = true;
                // signal we are leaving a target
                $this->inTarget = false;
                $this->postProcAndFlush( $this->outputFP, $tag, true );

            } elseif ( in_array( $name, $this->nodesToBuffer ) ) { // we are closing a critical CDATA section

                $this->bufferIsActive = false;
                $tag                  = $this->CDATABuffer . "</$name>";
                $this->CDATABuffer    = "";

                //flush to the pointer
                $this->postProcAndFlush( $this->outputFP, $tag );

            } elseif ( $name === $this->tuTagName ) {

                $tag = "";

                // handling </trans-unit> closure
                if ( !$this->targetWasWritten ) {

                    if ( isset( $this->transUnits[ $this->currentTransUnitId ] ) ) {
                        $tag = $this->rebuildTarget();
                    } else {
                        $tag = $this->createTargetTag( "", "" );
                    }

                }

                $tag                    .= "</$this->tuTagName>";
                $this->targetWasWritten = false;
                $this->postProcAndFlush( $this->outputFP, $tag );

            } elseif ( $this->bufferIsActive ) { // this is a tag ( <g | <mrk ) inside a seg or seg-source tag
                $this->CDATABuffer .= "</$name>";
                // Do NOT Flush
            } else { //generic tag closure do Nothing
                // flush to pointer
                $this->postProcAndFlush( $this->outputFP, $tag );
            }

        } else {
            //ok, nothing to be done; reset flag for next coming tag
            $this->isEmpty = false;
        }

        // try to signal that we are leaving a target
        $this->tryUnsetAltTrans( $name );

        // check if we are leaving a <trans-unit> (xliff v1.*) or <unit> (xliff v2.*)
        if ( $this->tuTagName === $name ) {
            $this->currentTransUnitIsTranslatable = null;
            $this->inTU                           = false;
            $this->hasWrittenCounts               = false;

            $this->resetCounts();
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
    protected function prepareTranslation( array $seg, string $transUnitTranslation = "" ): string {

        $segment     = Strings::removeDangerousChars( $seg [ 'segment' ] );
        $translation = Strings::removeDangerousChars( $seg [ 'translation' ] );

        if ( $seg [ 'translation' ] == '' ) {
            $translation = $segment;
        } else {
            if ( $this->callback instanceof XliffReplacerCallbackInterface ) {
                $error = ( !empty( $seg[ 'error' ] ) ) ? $seg[ 'error' ] : null;
                if ( $this->callback->thereAreErrors( $seg[ 'sid' ], $segment, $translation, [], $error ) ) {
                    $translation = '|||UNTRANSLATED_CONTENT_START|||' . $segment . '|||UNTRANSLATED_CONTENT_END|||';
                }
            }
        }

        $transUnitTranslation .= $seg[ 'prev_tags' ] . $this->rebuildMarks( $seg, $translation ) . ltrim( $seg[ 'succ_tags' ] );

        return $transUnitTranslation;
    }

    protected function rebuildMarks( array $seg, string $translation ): string {

        if ( $seg[ 'mrk_id' ] !== null && $seg[ 'mrk_id' ] != '' ) {
            $translation = "<mrk mid=\"" . $seg[ 'mrk_id' ] . "\" mtype=\"seg\">" . $seg[ 'mrk_prev_tags' ] . $translation . $seg[ 'mrk_succ_tags' ] . "</mrk>";
        }

        return $translation;

    }

    /**
     * This function creates a <target>
     *
     * @param string $translation
     * @param string $stateProp
     *
     * @return string
     */
    private function createTargetTag( string $translation, string $stateProp ): string {
        $targetLang = ' xml:lang="' . $this->targetLang . '"';
        $tag        = "<target $targetLang $stateProp>$translation</target>";
        $tag        .= "\n<count-group name=\"$this->currentTransUnitId\"><count count-type=\"x-matecat-raw\">" . $this->counts[ 'raw_word_count' ] . "</count><count count-type=\"x-matecat-weighted\">" . $this->counts[ 'eq_word_count' ] . '</count></count-group>';

        return $tag;

    }

    protected function rebuildTarget(): string {

        // init translation and state
        $translation  = '';
        $lastMrkState = null;
        $stateProp    = '';

        // we must reset the lastMrkId found because this is a new segment.
        $lastMrkId = -1;

        foreach ( $this->lastTransUnit as $pos => $seg ) {

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
            if ( (int)$seg[ "mrk_id" ] < 0 && $seg[ "mrk_id" ] !== null ) {
                $seg[ "mrk_id" ] = 0;
            }

            /*
             * WARNING:
             * For those seg-source that doesn't have a mrk ( having a mrk id === null )
             * ( null <= -1 ) === true
             * so, cast to int
             */
            if ( (int)$seg[ "mrk_id" ] <= $lastMrkId ) {
                break;
            }

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

            $lastMrkId = $seg[ "mrk_id" ];

            [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( $this->xliffVersion, $seg[ 'status' ], $lastMrkState );

        }

        //append translation
        return $this->createTargetTag( $translation, $stateProp );

    }

}