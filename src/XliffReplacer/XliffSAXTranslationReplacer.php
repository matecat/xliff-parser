<?php

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\Utils\Strings;

class XliffSAXTranslationReplacer extends AbstractXliffReplacer
{
    /**
     * @var array
     */
    private $nodesToCopy = [
        'source',
        'mda:metadata' ,
        'memsource:additionalTagData' ,
        'originalData' ,
        'seg-source' ,
        'value' ,
        'bpt' ,
        'ept' ,
        'ph' ,
        'st' ,
        'note' ,
        'context',
    ];

    public function replaceTranslation()
    {
        fwrite($this->outputFP, '<?xml version="1.0" encoding="UTF-8"?>');

        //create Sax parser
        $xmlParser = $this->initSaxParser();

        while ($this->currentBuffer = fread($this->originalFP, 4096)) {
            /*
               preprocess file
             */
            // obfuscate entities because sax automatically does html_entity_decode
            $temporary_check_buffer = preg_replace("/&(.*?);/", self::$INTERNAL_TAG_PLACEHOLDER . '$1' . self::$INTERNAL_TAG_PLACEHOLDER, $this->currentBuffer);

            $lastByte = $temporary_check_buffer[ strlen($temporary_check_buffer) - 1 ];

            //avoid cutting entities in half:
            //the last fread could have truncated an entity (say, '&lt;' in '&l'), thus invalidating the escaping
            //***** and if there is an & that it is not an entity, this is an infinite loop !!!!!

            $escape_AMP = false;

            // 9 is the max length of an entity. So, suppose that the & is at the end of buffer,
            // add 9 Bytes and substitute the entities, if the & is present and it is not at the end
            //it can't be a entity, exit the loop

            while (true) {
                $_ampPos = strpos($temporary_check_buffer, '&');

                //check for real entity or escape it to safely exit from the loop!!!
                if ($_ampPos === false or strlen(substr($temporary_check_buffer, $_ampPos)) > 9) {
                    $escape_AMP = true;
                    break;
                }

                //if an entity is still present, fetch some more and repeat the escaping
                $this->currentBuffer    .= fread($this->originalFP, 9);
                $temporary_check_buffer = preg_replace("/&(.*?);/", self::$INTERNAL_TAG_PLACEHOLDER . '$1' . self::$INTERNAL_TAG_PLACEHOLDER, $this->currentBuffer);
            }

            //free stuff outside the loop
            unset($temporary_check_buffer);

            $this->currentBuffer = preg_replace("/&(.*?);/", self::$INTERNAL_TAG_PLACEHOLDER . '$1' . self::$INTERNAL_TAG_PLACEHOLDER, $this->currentBuffer);
            if ($escape_AMP) {
                $this->currentBuffer = str_replace("&", self::$INTERNAL_TAG_PLACEHOLDER . 'amp' . self::$INTERNAL_TAG_PLACEHOLDER, $this->currentBuffer);
            }

            //get length of chunk
            $this->len = strlen($this->currentBuffer);

            //parse chunk of text
            if (!xml_parse($xmlParser, $this->currentBuffer, feof($this->originalFP))) {
                //if unable, die
                die(sprintf(
                    "XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($xmlParser)),
                    xml_get_current_line_number($xmlParser)
                ));
            }
            //get accumulated this->offset in document: as long as SAX pointer advances, we keep track of total bytes it has seen so far; this way, we can translate its global pointer in an address local to the current buffer of text to retrieve last char of tag
            $this->offset += $this->len;
        }

        // close Sax parser
        $this->closeSaxParser($xmlParser);

    }

    /**
     * @inheritDoc
     */
    protected function tagOpen($parser, $name, $attr)
    {
        // check if we are entering into a <trans-unit> (xliff v1.*) or <unit> (xliff v2.*)
        if ($this->tuTagName === $name) {
            $this->inTU = true;

            // get id
            // trim to first 100 characters because this is the limit on Matecat's DB
            $this->currentTransUnitId = substr($attr[ 'id' ], 0, 100);

            // `translate` attribute can be only yes or no
            if(isset($attr[ 'translate' ]) and $attr[ 'translate' ] === 'no'){
                $attr[ 'translate' ] = 'no';
            } else {
                $attr[ 'translate' ] = 'yes';
            }

            // current 'translate' attribute of the current trans-unit
            $this->currentTransUnitTranslate = isset($attr[ 'translate' ]) ? $attr[ 'translate' ] : 'yes';
        }

        if('source' === $name){
            $this->sourceAttributes = $attr;
        }

        if('mda:metadata' === $name){
            $this->unitContainsMda = true;
        }

        // check if we are entering into a <target>
        if ('target' === $name) {

            if($this->currentTransUnitTranslate === 'no'){
                $this->inTarget = false;
            } else {
                $this->inTarget = true;
            }
        }

        // check if we are inside a <target>, obviously this happen only if there are targets inside the trans-unit
        // <target> must be stripped to be replaced, so this check avoids <target> reconstruction
        if (!$this->inTarget) {

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
            if($this->xliffVersion === 2 and ( $name === 'notes' or $name === 'originalData' or $name === 'segment' or $name === 'ignorable') and $this->unitContainsMda === false ){
                if (isset($this->transUnits[ $this->currentTransUnitId ]) and !empty($this->transUnits[ $this->currentTransUnitId ]) and !$this->unitContainsMda and !$this->hasWrittenCounts) {

                    // we need to update counts here
                    $this->updateCounts();
                    $this->hasWrittenCounts = true;

                    $tag .= $this->getWordCountGroupForXliffV2($this->counts[ 'raw_word_count' ], $this->counts[ 'eq_word_count' ]);
                    $this->unitContainsMda = true;
                }
            }

            // construct tag
            $tag .= "<$name ";

            $lastMrkState = null;
            $stateProp   = '';

            foreach ($attr as $k => $v) {

                //if tag name is file, we must replace the target-language attribute
                if ($name === 'file' and $k === 'target-language' and !empty($this->targetLang)) {
                    //replace Target language with job language provided from constructor
                    $tag .= "$k=\"$this->targetLang\" ";
                } else {
                    $pos = 0;
                    if ($this->currentTransUnitId) {
                        $pos = current($this->transUnits[ $this->currentTransUnitId ]);
                    }

                    if ($name === $this->tuTagName) {

                        $sid = $this->segments[ $pos ][ 'sid' ];

                        // add `help-id` to xliff v.1*
                        // add `mtc:segment-id` to xliff v.2*
                        if($this->xliffVersion === 1 and strpos($tag, 'help-id') === false){
                            if (!empty($sid)) {
                                $tag .= "help-id=\"$sid\" ";
                            }
                        } elseif($this->xliffVersion === 2 and strpos($tag, 'mtc:segment-id') === false) {
                            if (!empty($sid)) {
                                $tag .= "mtc:segment-id=\"$sid\" ";
                            }
                        }

                    } elseif ('segment' === $name and $this->xliffVersion === 2) { // add state to segment in Xliff v2
                        list($stateProp, $lastMrkState) = $this->setTransUnitState($this->segments[ $pos ], $stateProp, $lastMrkState);
                    }

                    //normal tag flux, put attributes in it
                    $tag .= "$k=\"$v\" ";

                    // replace state for xliff v2
                    if ($stateProp) {
                        $pattern = '/state=\"(.*)\"/i';
                        $tag = preg_replace($pattern, $stateProp, $tag);
                    }
                }
            }

            // add oasis xliff 20 namespace
            if ($this->xliffVersion === 2 and $name === 'xliff' and !array_key_exists('xmlns:mda', $attr)) {
                $tag .= 'xmlns:mda="urn:oasis:names:tc:xliff:metadata:2.0"';
            }

            // add MateCat specific namespace, we want maybe add non-XLIFF attributes
            if ($name === 'xliff' and !array_key_exists('xmlns:mtc', $attr)) {
                $tag .= ' xmlns:mtc="https://www.matecat.com" ';
            }

            // trgLang
            if ($name === 'xliff') {
                $tag = preg_replace('/trgLang="(.*?)"/', 'trgLang="'.$this->targetLang.'"', $tag);
            }

            //this logic helps detecting empty tags
            //get current position of SAX pointer in all the stream of data is has read so far:
            //it points at the end of current tag
            $idx = xml_get_current_byte_index($parser);

            //check whether the bounds of current tag are entirely in current buffer or the end of the current tag
            //is outside current buffer (in the latter case, it's in next buffer to be read by the while loop);
            //this check is necessary because we may have truncated a tag in half with current read,
            //and the other half may be encountered in the next buffer it will be passed
            if (isset($this->currentBuffer[ $idx - $this->offset ])) {
                //if this tag entire lenght fitted in the buffer, the last char must be the last
                //symbol before the '>'; if it's an empty tag, it is assumed that it's a '/'
                $tmp_offset = $idx - $this->offset;
                $lastChar   = $this->currentBuffer[ $idx - $this->offset ];
            } else {
                //if it's out, simple use the last character of the chunk
                $tmp_offset = $this->len - 1;
                $lastChar   = $this->currentBuffer[ $this->len - 1 ];
            }

            //trim last space
            $tag = rtrim($tag);

            //detect empty tag
            $this->isEmpty = ($lastChar == '/' or $name == 'x');
            if ($this->isEmpty) {
                $tag .= '/';
            }

            //add tag ending
            $tag .= ">";

            //set a a Buffer for the segSource Source tag
            if ($this->bufferIsActive or in_array($name, $this->nodesToCopy)) { // we are opening a critical CDATA section

                //WARNING BECAUSE SOURCE AND SEG-SOURCE TAGS CAN BE EMPTY IN SOME CASES!!!!!
                //so check for isEmpty also in conjunction with name
                if ($this->isEmpty and ('source' === $name or 'seg-source' === $name)) {
                    $this->postProcAndFlush($this->outputFP, $tag);
                } else {
                    //these are NOT source/seg-source/value empty tags, THERE IS A CONTENT, write it in buffer
                    $this->bufferIsActive = true;
                    $this->CDATABuffer    .= $tag;
                }
            } else {
                $this->postProcAndFlush($this->outputFP, $tag);
            }
        }

        // update segmentPositionInTu

        if($this->xliffVersion === 1 and $this->inTU and $name === 'source'){
            $this->segmentPositionInTu++;
        }

        if($this->xliffVersion === 2 and $this->inTU and $name === 'segment'){
            $this->segmentPositionInTu++;
        }
    }

    /**
     * @inheritDoc
     */
    protected function tagClose($parser, $name)
    {
        $tag = '';

        /**
         * if is a tag within <target> or
         * if it is an empty tag, do not add closing tag because we have already closed it in
         *
         * self::tagOpen method
         */
        if (!$this->isEmpty and !($this->inTarget and $name !== 'target')) {

            if (!$this->inTarget) {
                $tag = "</$name>";
            }

            if ('target' == $name) {

                if($this->currentTransUnitTranslate === 'no') {
                    // do nothing
                } elseif (isset($this->transUnits[ $this->currentTransUnitId ])) {

                    // get translation of current segment, by indirect indexing: id -> positional index -> segment
                    // actually there may be more that one segment to that ID if there are two mrk of the same source segment

                    $listOfSegmentsIds = $this->transUnits[ $this->currentTransUnitId ];

                    // $currentSegmentId
                    if(!empty($listOfSegmentsIds)){
                        $this->setCurrentSegmentArray($listOfSegmentsIds);
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

                    $warning    = false;
                    $last_value = null;
                    for ($i = 0; $i < count($listOfSegmentsIds); $i++) {
                        if (isset($listOfSegmentsIds[ $i ])) {
                            $id = $listOfSegmentsIds[ $i ];
                            if (isset($this->segments[ $id ]) and ($i == 0 or $last_value + 1 == $listOfSegmentsIds[ $i ])) {
                                $last_value            = $listOfSegmentsIds[ $i ];
                                $this->lastTransUnit[] = $this->segments[ $id ];
                            }
                        } else {
                            $warning = true;
                        }
                    }

                    if ($warning) {
                        if (null !== $this->logger) {
                            $this->logger->warning("WARNING: PHP Notice polling. CurrentId: '" . $this->currentTransUnitId . "' - Filename: '" . $this->segments[ 0 ][ 'filename' ] . "' - First Segment: '" . $this->segments[
                                    0 ][ 'sid' ] . "'");
                        }
                    }

                    // init translation and state
                    $translation  = '';
                    $lastMrkState = null;
                    $stateProp   = '';

                    // we must reset the lastMrkId found because this is a new segment.
                    $lastMrkId      = -1;

                    if ($this->xliffVersion === 2) {
                        $seg = $this->segments[ $this->currentSegmentArray['sid'] ];

                        // update counts
                        if(!$this->hasWrittenCounts and !empty($seg)){
                            $this->updateSegmentCounts($seg);
                        }

                        // delete translations so the prepareSegment
                        // will put source content in target tag
                        if ($this->sourceInTarget) {
                            $seg[ 'translation' ] = '';
                            $this->resetCounts();
                        }

                        // append $translation
                        $translation = $this->prepareTranslation($seg, $translation);

                        list($stateProp, $lastMrkState) = $this->setTransUnitState($seg, $stateProp, $lastMrkState);
                    } else {
                        foreach ($listOfSegmentsIds as $pos => $id) {

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
                            if ((int)$this->segments[ $id ][ "mrk_id" ] < 0 and $this->segments[ $id ][ "mrk_id" ] !== null) {
                                $this->segments[ $id ][ "mrk_id" ] = 0;
                            }

                            /*
                             * WARNING:
                             * For those seg-source that does'nt have a mrk ( having a mrk id === null )
                             * ( null <= -1 ) === true
                             * so, cast to int
                             */
                            if ((int)$this->segments[ $id ][ "mrk_id" ] <= $lastMrkId) {
                                break;
                            }

                            // set $this->currentSegment
                            $seg = $this->segments[ $id ];

                            // update counts
                            if(!empty($seg)){
                                $this->updateSegmentCounts($seg);
                            }

                            // delete translations so the prepareSegment
                            // will put source content in target tag
                            if ($this->sourceInTarget) {
                                $seg[ 'translation' ] = '';
                                $this->resetCounts();
                            }

                            // append $translation
                            $translation = $this->prepareTranslation($seg, $translation);

                            // for xliff 2 we need $this->transUnits[ $this->currentId ] [ $pos ] for populating metadata

                            unset($this->transUnits[ $this->currentTransUnitId ] [ $pos ]);

                            $lastMrkId = $this->segments[ $id ][ "mrk_id" ];

                            list($stateProp, $lastMrkState) = $this->setTransUnitState($seg, $stateProp, $lastMrkState);
                        }
                    }

                    //append translation
                    $targetLang = '';
                    if ($this->xliffVersion === 1) {
                        $targetLang = ' xml:lang="'.$this->targetLang.'"';
                    }

                    $tag = $this->buildTranslateTag($targetLang, $stateProp, $translation, $this->counts[ 'raw_word_count' ], $this->counts[ 'eq_word_count' ]);
                }

                // signal we are leaving a target
                $this->targetWasWritten = true;
                $this->inTarget         = false;
                $this->postProcAndFlush($this->outputFP, $tag, $treatAsCDATA = true);
            } elseif (in_array($name, $this->nodesToCopy)) { // we are closing a critical CDATA section

                $this->bufferIsActive = false;

                // only for Xliff 2.*
                // write here <mda:metaGroup> and <mda:meta> if already present in the <unit>
                if('mda:metadata' === $name and $this->unitContainsMda and $this->xliffVersion === 2 and !$this->hasWrittenCounts){

                    // we need to update counts here
                    $this->updateCounts();
                    $this->hasWrittenCounts = true;

                    $tag = $this->CDATABuffer;
                    $tag .= $this->getWordCountGroupForXliffV2($this->counts[ 'raw_word_count' ], $this->counts[ 'eq_word_count' ], false);
                    $tag .= "    </mda:metadata>";

                } else {
                    $tag = $this->CDATABuffer."</$name>";
                }

                $this->CDATABuffer    = "";

                //flush to pointer
                $this->postProcAndFlush($this->outputFP, $tag);
            } elseif ('segment' === $name) {

                // only for Xliff 2.*
                // if segment has no <target> add it BEFORE </segment>
                if($this->xliffVersion === 2 and !$this->targetWasWritten){

                    $seg = $this->getCurrentSegment();

                    // copy attr from <source>
                    $tag = '<target';
                    foreach ($this->sourceAttributes as $k => $v) {
                        $tag .= " $k=\"$v\"";
                    }

                    $tag .= '>'.$seg['translation'].'</target></segment>';
                }

                $this->postProcAndFlush($this->outputFP, $tag);

                // we are leaving <segment>, reset $segmentHasTarget
                $this->targetWasWritten = false;

            } elseif($name === 'trans-unit') {

                // only for Xliff 1.*
                // handling </trans-unit> closure
                if(!$this->targetWasWritten){
                    $seg = $this->getCurrentSegment();
                    $lastMrkState = null;
                    $stateProp   = '';
                    $tag = '';

                    // if there is translation available insert <target> BEFORE </trans-unit>
                    if(isset($seg['translation'])){
                        list($stateProp, $lastMrkState) = $this->setTransUnitState($seg, $stateProp, $lastMrkState);
                        $tag .= $this->createTargetTag($seg['translation'], $stateProp);
                    }

                    $tag .= '</trans-unit>';
                    $this->postProcAndFlush($this->outputFP, $tag);
                } else {
                    $this->postProcAndFlush($this->outputFP, '</trans-unit>');
                }
            } elseif ($this->bufferIsActive) { // this is a tag ( <g | <mrk ) inside a seg or seg-source tag
                $this->CDATABuffer .= "</$name>";
            // Do NOT Flush
            } else { //generic tag closure do Nothing
                // flush to pointer
                $this->postProcAndFlush($this->outputFP, $tag);
            }
        } elseif ($this->CDATABuffer === '<note/>' and $this->bufferIsActive === true) {
            $this->postProcAndFlush($this->outputFP, '<note/>');
            $this->bufferIsActive = false;
            $this->CDATABuffer    = '';
            $this->isEmpty        = false;
        } else {
            //ok, nothing to be done; reset flag for next coming tag
            $this->isEmpty = false;
        }

        // check if we are leaving a <trans-unit> (xliff v1.*) or <unit> (xliff v2.*)
        if ($this->tuTagName === $name) {
            $this->currentTransUnitTranslate = null;
            $this->inTU = false;
            $this->segmentPositionInTu = -1;
            $this->unitContainsMda = false;
            $this->hasWrittenCounts = false;
            $this->sourceAttributes = [];
        }
    }

    /**
     * Set the current segment array (with segment id and trans-unit id)
     *
     * @param array $listOfSegmentsIds
     */
    private function setCurrentSegmentArray(array $listOfSegmentsIds = [])
    {
        // $currentSegmentId
        if (empty($this->currentSegmentArray)) {
            $this->currentSegmentArray = [
                'sid' => $listOfSegmentsIds[0],
                'tid' => $this->currentTransUnitId,
            ];
        } else {
            if ($this->currentSegmentArray['tid'] === $this->currentTransUnitId) {
                $key = array_search($this->currentSegmentArray['sid'], $listOfSegmentsIds);
                $this->currentSegmentArray['sid'] = $listOfSegmentsIds[$key+1];
                $this->currentSegmentArray['tid'] = $this->currentTransUnitId;
            } else {
                $this->currentSegmentArray = [
                    'sid' => $listOfSegmentsIds[0],
                    'tid' => $this->currentTransUnitId,
                ];
            }
        }
    }

    /**
     * Update counts
     */
    private function updateCounts()
    {
        // populate counts
        $listOfSegmentsIds = $this->transUnits[ $this->currentTransUnitId ];

        // $currentSegmentId
        if(!empty($listOfSegmentsIds)){
            $this->setCurrentSegmentArray($listOfSegmentsIds);
        }

        if ($this->xliffVersion === 2) {
            $seg = $this->segments[ $this->currentSegmentArray[ 'sid' ] ];
            if(!empty($seg)){
                $this->updateSegmentCounts($seg);
            }
        } else {
            foreach ($listOfSegmentsIds as $pos => $id) {
                $seg = $this->segments[ $id ];
                if(!empty($seg)){
                    $this->updateSegmentCounts( $seg );
                }
            }
        }

        $this->currentSegmentArray = [];
    }

    /**
     * @param array $seg
     */
    private function updateSegmentCounts(array $seg = [])
    {
        $this->counts[ 'raw_word_count' ] += $seg['raw_word_count'];
        $this->counts[ 'eq_word_count' ] += (floor($seg[ 'eq_word_count' ] * 100) / 100);
    }

    private function resetCounts()
    {
        $this->counts[ 'raw_word_count' ] = 0;
        $this->counts[ 'eq_word_count' ] = 0;
    }

    /**
     * prepare segment tagging for xliff insertion
     *
     * @param array  $seg
     * @param string $transUnitTranslation
     *
     * @return string
     */
    protected function prepareTranslation($seg, $transUnitTranslation = "")
    {
        $endTags = "";

        $segment     = Strings::removeDangerousChars($seg [ 'segment' ]);
        $translation = Strings::removeDangerousChars($seg [ 'translation' ]);
        $dataRefMap  = (isset($seg['data_ref_map']) and $seg['data_ref_map'] !== null) ? Strings::jsonToArray($seg['data_ref_map']) : [];

        if (is_null($seg [ 'translation' ]) or $seg [ 'translation' ] == '') {
            $translation = $segment;
        } else {
            if ($this->callback) {
                if ($this->callback->thereAreErrors($segment, $translation, $dataRefMap)) {
                    $translation = '|||UNTRANSLATED_CONTENT_START|||' . $segment . '|||UNTRANSLATED_CONTENT_END|||';
                }
            }
        }

        // for xliff v2 we ignore the marks on purpose
        if ($this->xliffVersion === 2) {
            return $translation;
        }

        if ($seg[ 'mrk_id' ] !== null and $seg[ 'mrk_id' ] != '') {
            if ($this->targetLang === 'ja-JP') {
                $seg[ 'mrk_succ_tags' ] = ltrim($seg[ 'mrk_succ_tags' ]);
            }

            $translation = "<mrk mid=\"" . $seg[ 'mrk_id' ] . "\" mtype=\"seg\">" . $seg[ 'mrk_prev_tags' ] . $translation . $seg[ 'mrk_succ_tags' ] . "</mrk>";
        }

        $transUnitTranslation .= $seg[ 'prev_tags' ] . $translation . $endTags . $seg[ 'succ_tags' ];

        return $transUnitTranslation;
    }

    /**
     * @param $targetLang
     * @param $stateProp
     * @param $translation
     * @param $rawWordCount
     * @param $eqWordCount
     *
     * @return string
     */
    private function buildTranslateTag($targetLang, $stateProp, $translation, $rawWordCount, $eqWordCount)
    {
        switch ($this->xliffVersion) {
            case 1:
            default:
                $tag = "<target $targetLang $stateProp>$translation</target>";

                // if it's a Trados file don't append count group
                if(get_class($this) !== SdlXliffSAXTranslationReplacer::class){
                    $tag .= $this->getWordCountGroup($rawWordCount, $eqWordCount);
                }

                return $tag;

            case 2:
                return "<target>$translation</target>";
        }
    }

    /**
     * @param $raw_word_count
     * @param $eq_word_count
     *
     * @return string
     */
    private function getWordCountGroup($raw_word_count, $eq_word_count)
    {
        return "\n<count-group name=\"$this->currentTransUnitId\"><count count-type=\"x-matecat-raw\">$raw_word_count</count><count count-type=\"x-matecat-weighted\">$eq_word_count</count></count-group>";
    }

    /**
     * @return array
     */
    private function getCurrentSegment()
    {
        if($this->currentTransUnitTranslate === 'yes' and isset($this->transUnits[$this->currentTransUnitId])){
            $index = $this->transUnits[$this->currentTransUnitId][$this->segmentPositionInTu];

            if(isset($this->segments[$index])){
                return $this->segments[$index];
            }
        }

        return [];
    }

    /**
     * This function create a <target>
     *
     * @param $translation
     * @param $stateProp
     *
     * @return string
     */
    private function createTargetTag($translation, $stateProp)
    {
        $targetLang = 'xml:lang="'.$this->targetLang.'"';

        return $this->buildTranslateTag($targetLang, $stateProp, $translation, $this->counts[ 'raw_word_count' ], $this->counts[ 'eq_word_count' ]);
    }

    /**
     * @param      $raw_word_count
     * @param      $eq_word_count
     * @param bool $withMetadataTag
     *
     * @return string
     */
    private function getWordCountGroupForXliffV2($raw_word_count, $eq_word_count, $withMetadataTag = true)
    {
        if($withMetadataTag === false){
            return "    <mda:metaGroup category=\"row_xml_attribute\">
                                <mda:meta type=\"x-matecat-raw\">$raw_word_count</mda:meta>
                                <mda:meta type=\"x-matecat-weighted\">$eq_word_count</mda:meta>
                            </mda:metaGroup>
                    ";
        }

        return "<mda:metadata>
                <mda:metaGroup category=\"row_xml_attribute\">
                    <mda:meta type=\"x-matecat-raw\">$raw_word_count</mda:meta>
                    <mda:meta type=\"x-matecat-weighted\">$eq_word_count</mda:meta>
                </mda:metaGroup>
            </mda:metadata>";
    }

    /**
     * @param $seg
     * @param $state_prop
     * @param $lastMrkState
     *
     * @return array
     */
    private function setTransUnitState($seg, $state_prop, $lastMrkState)
    {
        switch ($seg[ 'status' ]) {

            case TranslationStatus::STATUS_FIXED:
            case TranslationStatus::STATUS_APPROVED:
                if ($lastMrkState == null or $lastMrkState == TranslationStatus::STATUS_APPROVED) {
                    $state_prop   = ($this->xliffVersion === 2) ? "state=\"reviewed\"": "state=\"signed-off\"";
                    $lastMrkState = TranslationStatus::STATUS_APPROVED;
                }
                break;

            case TranslationStatus::STATUS_TRANSLATED:
                if ($lastMrkState == null or $lastMrkState == TranslationStatus::STATUS_TRANSLATED or $lastMrkState == TranslationStatus::STATUS_APPROVED) {
                    $state_prop   = "state=\"translated\"";
                    $lastMrkState = TranslationStatus::STATUS_TRANSLATED;
                }
                break;

            case TranslationStatus::STATUS_REJECTED:  // if there is a mark REJECTED and there is not a DRAFT, all the trans-unit is REJECTED. In V2 there is no way to mark
            case TranslationStatus::STATUS_REBUTTED:
                if (($lastMrkState == null) or ($lastMrkState != TranslationStatus::STATUS_NEW or $lastMrkState != TranslationStatus::STATUS_DRAFT)) {
                    $state_prop   = ($this->xliffVersion === 2) ? "state=\"initial\"" : "state=\"needs-review-translation\"";
                    $lastMrkState = TranslationStatus::STATUS_REJECTED;
                }
                break;

            case TranslationStatus::STATUS_NEW:
                if (($lastMrkState == null) or $lastMrkState != TranslationStatus::STATUS_DRAFT) {
                    $state_prop   = ($this->xliffVersion === 2) ? "state=\"initial\"" : "state=\"new\"";
                    $lastMrkState = TranslationStatus::STATUS_NEW;
                }
                break;

            case TranslationStatus::STATUS_DRAFT:
                $state_prop   = "state=\"needs-translation\"";
                $lastMrkState = TranslationStatus::STATUS_DRAFT;
                break;
            default:
                // this is the case when a segment is not showed in cattool, so the row in
                // segment_translations does not exists and
                // ---> $seg[ 'status' ] is NULL
                if ($lastMrkState == null) { //this is the first MRK ID
                    $state_prop   = "state=\"translated\"";
                    $lastMrkState = TranslationStatus::STATUS_TRANSLATED;
                } else { /* Do nothing and preserve the last state */
                }
                break;
        }

        return [ $state_prop, $lastMrkState ];
    }

    /**
     * @inheritDoc
     */
    protected function characterData($parser, $data)
    {
        // don't write <target> data
        if (!$this->inTarget and !$this->bufferIsActive) {
            $this->postProcAndFlush($this->outputFP, $data);
        } elseif ($this->bufferIsActive) {
            $this->CDATABuffer .= $data;
        }
    }
}
