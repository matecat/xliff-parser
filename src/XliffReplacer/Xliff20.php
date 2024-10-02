<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 02/08/24
 * Time: 17:51
 *
 */

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Utils\Strings;

class Xliff20 extends AbstractXliffReplacer {

    /**
     * @var int
     */
    private int $mdaGroupCounter = 0;
    /**
     * @var bool
     */
    protected bool $unitContainsMda = false;   // check if <unit> already contains a <mda:metadata> (forXliff v 2.*)

    /**
     * @var string
     */
    protected string $alternativeMatchesTag = 'mtc:matches';

    /**
     * @var string
     */
    protected string $tuTagName = 'unit';

    /**
     * @var string
     */
    protected string $namespace = "matecat";       // Custom namespace

    /**
     * @var array
     */
    protected array $nodesToBuffer = [
            'source',
            'mda:metadata',
            'memsource:additionalTagData',
            'originalData',
            'note'
    ];

    /**
     * @inheritDoc
     */
    protected function tagOpen( $parser, string $name, array $attr ) {

        $this->handleOpenUnit( $name, $attr );

        if ( 'mda:metadata' === $name ) {
            $this->unitContainsMda = true;
        }

        $this->trySetAltTrans( $name );;
        $this->checkSetInTarget( $name );

        // open buffer
        $this->setInBuffer( $name );

        // check if we are inside a <target>, obviously this happen only if there are targets inside the trans-unit
        // <target> must be stripped to be replaced, so this check avoids <target> reconstruction
        if ( !$this->inTarget ) {

            // We need bufferIsActive for not target nodes with currentTransUnitIsTranslatable = 'NO'
            if($name === 'target' and $this->currentTransUnitIsTranslatable === 'no'){
                $this->bufferIsActive = true;
            }

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
            if ( in_array( $name, [ 'notes', 'originalData', 'segment', 'ignorable' ] ) &&
                    $this->unitContainsMda === false &&
                    !empty( $this->transUnits[ $this->currentTransUnitId ] ) &&
                    !$this->hasWrittenCounts
            ) {
                // we need to update counts here
                $this->updateCounts();
                $this->hasWrittenCounts = true;
                $tag                    .= $this->getWordCountGroupForXliffV2();
                $this->unitContainsMda  = true;
            }

            // construct tag
            $tag .= "<$name ";

            foreach ( $attr as $k => $v ) {
                //normal tag flux, put attributes in it but skip for translation state and set the right value for the attribute
                if ( $k != 'state' ) {
                    $tag .= "$k=\"$v\" ";
                }
            }

            $seg = $this->getCurrentSegment();

            if ( $name === $this->tuTagName && !empty( $seg ) && isset( $seg[ 'sid' ] ) ) {

                // add `matecat:segment-id` to xliff v.2*
                if ( strpos( $tag, 'matecat:segment-id' ) === false ) {
                    $tag .= "matecat:segment-id=\"{$seg[ 'sid' ]}\" ";
                }

            }

            // replace state for xliff v2
            if ( 'segment' === $name ) { // add state to segment in Xliff v2
                [ $stateProp, ] = StatusToStateAttribute::getState( $this->xliffVersion, $seg[ 'status' ] );
                $tag .= $stateProp;
            }

            $tag = $this->handleOpenXliffTag( $name, $attr, $tag );

            $this->checkForSelfClosedTagAndFlush( $parser, $tag );

        }

    }

    /**
     * @param string $name
     * @param array  $attr
     * @param string $tag
     *
     * @return string
     */
    protected function handleOpenXliffTag( string $name, array $attr, string $tag ): string {
        $tag = parent::handleOpenXliffTag( $name, $attr, $tag );
        // add oasis xliff 20 namespace
        if ( $name === 'xliff' && !array_key_exists( 'xmlns:mda', $attr ) ) {
            $tag .= 'xmlns:mda="urn:oasis:names:tc:xliff:metadata:2.0"';
        }

        return $tag;
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

            // write closing tag if is not a target
            // EXCLUDE the target nodes with currentTransUnitIsTranslatable = 'NO'
            if ( !$this->inTarget and $this->currentTransUnitIsTranslatable !== 'no' ) {
                $tag = "</$name>";
            }

            if ( 'target' == $name && !$this->inAltTrans ) {

                if ( isset( $this->transUnits[ $this->currentTransUnitId ] ) ) {

                    $seg = $this->getCurrentSegment();

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
                    $translation = $this->prepareTranslation( $seg );

                    //append translation
                    $tag = "<target>$translation</target>";

                } elseif( !empty($this->CDATABuffer) and $this->currentTransUnitIsTranslatable === 'no' ) {

                    // These are target nodes with currentTransUnitIsTranslatable = 'NO'
                    $this->bufferIsActive = false;
                    $tag                  = $this->CDATABuffer . "</$name>";
                    $this->CDATABuffer    = "";
                }

                // signal we are leaving a target
                $this->targetWasWritten = true;
                $this->inTarget         = false;
                $this->postProcAndFlush( $this->outputFP, $tag, true );

            } elseif ( in_array( $name, $this->nodesToBuffer ) ) { // we are closing a critical CDATA section

                $this->bufferIsActive = false;

                // only for Xliff 2.*
                // write here <mda:metaGroup> and <mda:meta> if already present in the <unit>
                if ( 'mda:metadata' === $name && $this->unitContainsMda && !$this->hasWrittenCounts ) {

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

                //flush to the pointer
                $this->postProcAndFlush( $this->outputFP, $tag );

            } elseif ( 'segment' === $name ) {

                // only for Xliff 2.*
                // if segment has no <target> add it BEFORE </segment>
                if ( !$this->targetWasWritten ) {

                    $seg = $this->getCurrentSegment();

                    if ( isset( $seg[ 'translation' ] ) ) {

                        $translation = $this->prepareTranslation( $seg );
                        // replace the tag
                        $tag = "<target>$translation</target>";

                        $tag .= '</segment>';

                    }

                }

                // update segmentPositionInTu
                $this->segmentInUnitPosition++;

                $this->postProcAndFlush( $this->outputFP, $tag );

                // we are leaving <segment>, reset $segmentHasTarget
                $this->targetWasWritten = false;

            } elseif ( $this->bufferIsActive ) { // this is a tag ( <g | <mrk ) inside a seg or seg-source tag
                $this->CDATABuffer .= "</$name>";
                // Do NOT Flush
            } else { //generic tag closure do Nothing
                // flush to pointer
                $this->postProcAndFlush( $this->outputFP, $tag );
            }
        } elseif ( in_array( $name, $this->nodesToBuffer ) ) {

            $this->isEmpty        = false;
            $this->bufferIsActive = false;
            $tag                  = $this->CDATABuffer;
            $this->CDATABuffer    = "";

            //flush to the pointer
            $this->postProcAndFlush( $this->outputFP, $tag );

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
            $this->unitContainsMda                = false;
            $this->hasWrittenCounts               = false;

            $this->resetCounts();
        }
    }

    /**
     * Update counts
     */
    private function updateCounts() {

        $seg = $this->getCurrentSegment();
        if ( !empty( $seg ) ) {
            $this->updateSegmentCounts( $seg );
        }

    }

    /**
     * @param bool $withMetadataTag
     *
     * @return string
     */
    private function getWordCountGroupForXliffV2( bool $withMetadataTag = true ): string {

        $this->mdaGroupCounter++;
        $segments_count_array = $this->counts[ 'segments_count_array' ];

        $tag = '';

        if ( $withMetadataTag === true ) {
            $tag .= '<mda:metadata>';
        }

        $index = 0;
        foreach ( $segments_count_array as $segments_count_item ) {

            $id = 'word_count_tu.' . $this->currentTransUnitId . '.' . $index;
            $index++;

            $tag .= "    <mda:metaGroup id=\"" . $id . "\" category=\"row_xml_attribute\">
                                <mda:meta type=\"x-matecat-raw\">" . $segments_count_item[ 'raw_word_count' ] . "</mda:meta>
                                <mda:meta type=\"x-matecat-weighted\">" . $segments_count_item[ 'eq_word_count' ] . "</mda:meta>
                            </mda:metaGroup>";
        }

        if ( $withMetadataTag === true ) {
            $tag .= '</mda:metadata>';
        }

        return $tag;

    }

    /**
     * prepare segment tagging for xliff insertion
     *
     * @param array $seg
     *
     * @return string
     */
    protected function prepareTranslation( array $seg ): string {

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

        return $translation;

    }

}