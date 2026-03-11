<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Utils\Strings;
use XMLParser;

class Xliff12 extends AbstractXliffReplacer
{

    /** @var array<int, string> */
    protected array $nodesToBuffer = [
        'source',
        'seg-source',
        'note',
        'context-group'
    ];

    protected string $tuTagName = 'trans-unit';

    protected string $alternativeMatchesTag = 'alt-trans';

    protected string $namespace = "mtc";

    /**
     * @inheritDoc
     */
    protected function tagOpen(XMLParser $parser, string $name, array $attr): void
    {
        $this->handleOpenUnit($name, $attr);

        $this->trySetAltTrans($name);
        $this->checkSetInTarget($name);

        // open buffer
        $this->setInBuffer($name);

        // check if we are inside a <target>, obviously this happen only if there are targets inside the trans-unit
        // <target> must be stripped to be replaced, so this check avoids <target> reconstruction
        if (!$this->inTarget) {
            $tag = '';

            // construct tag
            $tag .= "<$name ";

            foreach ($attr as $k => $v) {
                //if tag name is file, we must replace the target-language attribute
                if ($name === 'file' && $k === 'target-language' && !empty($this->targetLang)) {
                    //replace Target language with job language provided from constructor
                    $tag .= "$k=\"$this->targetLang\" ";
                } else {
                    $tag .= "$k=\"$v\" ";
                }
            }

            $seg = $this->getCurrentSegment();

            if (
                $name === $this->tuTagName &&
                !empty($seg) &&
                isset($seg['sid'])
            ) {
                // add `help-id` to xliff v.1*
                if (!str_contains($tag, 'help-id')) {
                    $tag .= "help-id=\"{$seg[ 'sid' ]}\" ";
                }
            }

            $tag = $this->handleOpenXliffTag($name, $attr, $tag);

            $this->checkForSelfClosedTagAndFlush($parser, $tag);
        }
    }


    /**
     * @inheritDoc
     */
    protected function tagClose(XMLParser $parser, string $name): void
    {
        $tag = '';

        /**
         * if is a tag within <target> or
         * if it is an empty tag, do not add closing tag because we have already closed it in
         *
         * self::tagOpen method
         */
        if (!$this->isEmpty) {
            // write closing tag if is not a target
            // EXCLUDE the target nodes with currentTransUnitIsTranslatable = 'NO'
            if (!$this->inTarget && $this->currentTransUnitIsTranslatable !== 'no') {
                $tag = "</$name>";
            }

            if ('target' == $name && !$this->inAltTrans) {
                if (isset($this->transUnits[$this->currentTransUnitId])) {
                    // get translation of current segment, by indirect indexing: id -> positional index -> segment
                    // actually there may be more than one segment to that ID if there are two mrk of the same source segment
                    $tag = $this->rebuildTarget();
                } elseif (!empty($this->CDATABuffer) && $this->currentTransUnitIsTranslatable === 'no') {
                    // These are target nodes with currentTransUnitIsTranslatable = 'NO'
                    $this->bufferIsActive = false;
                    $tag = $this->CDATABuffer . "</$name>";
                    $this->CDATABuffer = "";
                }

                $this->targetWasWritten = true;
                // signal we are leaving a target
                $this->inTarget = false;
                $this->postProcAndFlush($this->outputFP, $tag, true);
            } elseif (in_array($name, $this->nodesToBuffer)) { // we are closing a critical CDATA section

                $this->bufferIsActive = false;
                $tag = $this->CDATABuffer . "</$name>";
                $this->CDATABuffer = "";

                //flush to the pointer
                $this->postProcAndFlush($this->outputFP, $tag);
            } elseif ($name === $this->tuTagName) {
                $tag = "";

                // handling </trans-unit> closure
                if (!$this->targetWasWritten) {
                    if (isset($this->transUnits[$this->currentTransUnitId])) {
                        $tag = $this->rebuildTarget();
                    } else {
                        $tag = $this->createTargetTag("", "");
                    }
                }

                $tag .= "</$this->tuTagName>";
                $this->targetWasWritten = false;
                $this->postProcAndFlush($this->outputFP, $tag);
            } elseif ($this->bufferIsActive) { // this is a tag ( <g | <mrk ) inside a seg or seg-source tag
                $this->CDATABuffer .= "</$name>";
                // Do NOT Flush
            } else { //generic tag closure do Nothing
                // flush to pointer
                $this->postProcAndFlush($this->outputFP, $tag);
            }
        } elseif (in_array($name, $this->nodesToBuffer)) {
            $this->isEmpty = false;
            $this->bufferIsActive = false;
            $tag = $this->CDATABuffer;
            $this->CDATABuffer = "";

            //flush to the pointer
            $this->postProcAndFlush($this->outputFP, $tag);
        } else {
            //ok, nothing to be done; reset flag for next coming tag
            $this->isEmpty = false;
        }

        // try to signal that we are leaving a target
        $this->tryUnsetAltTrans($name);

        // check if we are leaving a <trans-unit> (xliff v1.*) or <unit> (xliff v2.*)
        if ($this->tuTagName === $name) {
            $this->currentTransUnitIsTranslatable = null;
            $this->inTU = false;
            $this->hasWrittenCounts = false;

            $this->resetCounts();
        }
    }

    /**
     * Prepare segment tagging for xliff insertion.
     *
     * @param array<string, mixed> $seg Segment data
     * @param string $transUnitTranslation Current translation
     *
     * @return string Prepared translation
     */
    protected function prepareTranslation(array $seg, string $transUnitTranslation = ""): string
    {
        $segment = Strings::removeDangerousChars($seg ['segment']);
        $translation = Strings::removeDangerousChars($seg ['translation']);

        if (!isset($seg['translation'])) {
            $translation = $segment;
        } elseif ($this->callback instanceof XliffReplacerCallbackInterface) {
            $error = (!empty($seg['error'])) ? $seg['error'] : null;
            if ($this->callback->thereAreErrors($seg['sid'], $segment, $translation, [], $error)) {
                $translation = '|||UNTRANSLATED_CONTENT_START|||' . $segment . '|||UNTRANSLATED_CONTENT_END|||';
            }
        }

        $transUnitTranslation .= $seg['prev_tags'] . $this->rebuildMarks($seg, $translation) . ltrim(
                $seg['succ_tags'] ?? ''
            );

        return $transUnitTranslation;
    }

    /**
     * Rebuild mrk tags around translation.
     *
     * @param array<string, mixed> $seg Segment data
     * @param string $translation Translation text
     *
     * @return string Translation with mrk tags
     */
    protected function rebuildMarks(array $seg, string $translation): string
    {
        if ($seg['mrk_id'] !== null && $seg['mrk_id'] != '') {
            $translation = "<mrk mid=\"" . $seg['mrk_id'] . "\" mtype=\"seg\">" . $seg['mrk_prev_tags'] . $translation . $seg['mrk_succ_tags'] . "</mrk>";
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
    protected function createTargetTag(string $translation, string $stateProp): string
    {
        $targetLang = ' xml:lang="' . $this->targetLang . '"';
        $tag = "<target $targetLang $stateProp>$translation</target>";
        $tag .= "\n<count-group name=\"$this->currentTransUnitId\"><count count-type=\"x-matecat-raw\">" . $this->counts['raw_word_count'] . "</count><count count-type=\"x-matecat-weighted\">" . $this->counts['eq_word_count'] . '</count></count-group>';

        return $tag;
    }

    protected function rebuildTarget(): string
    {
        // Initialize variables for building the target translation
        $translation = '';
        $lastMrkState = null;
        $stateProp = '';

        // Reset marker ID counter for this new segment
        $lastMrkId = -1;

        // Iterate through each segment in the translation unit
        foreach ($this->lastTransUnit as $seg) {
            /*
             * In the Xliff file there is possible to have multiple segments with the same internal_id.
             *
             * Marker position validation logic:
             * Ensures markers maintain proper sequential order within segments.
             * If the current marker ID is less than or equal to the last one processed,
             *    it indicates we've moved to a different segment with the same internal_id.
             * So, we stop processing this translation unit.
             */

            // Convert negative marker IDs to 0 for the first marker in a segment
            if ((int)$seg["mrk_id"] < 0 && $seg["mrk_id"] !== null) {
                $seg["mrk_id"] = 0;
            }

            /*
             * Check if we've reached the end of the current segment's markers.
             * Note: null marker IDs (cast to int) will be <= -1, triggering the break.
             */
            if ((int)$seg["mrk_id"] <= $lastMrkId) {
                break;
            }

            // Update word count statistics for non-empty segments
            if (!empty($seg)) {
                $this->updateSegmentCounts($seg);
            }

            // If source-in-target mode is enabled, clear translation and reset counts
            // This causes prepareSegment to use source content in the target tag
            if ($this->sourceInTarget) {
                $seg['translation'] = null;
                $this->resetCounts();
            }

            // Build the translation string by appending this segment's translation
            $translation = $this->prepareTranslation($seg, $translation);

            // Track the last processed marker ID
            $lastMrkId = $seg["mrk_id"];

            // Determine and track the translation state attribute based on segment status
            [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
                $this->xliffVersion,
                $seg['status'],
                $lastMrkState
            );
        }

        // Wrap the complete translation in a target XML tag with language and state attributes
        return $this->createTargetTag($translation, $stateProp);
    }

    /**
     * @inheritDoc
     *
     * In XLIFF 1.2, this method overrides the parent to retrieve the first segment
     * of a trans-unit. The [0] index is required because $this->transUnits maps
     * trans-unit IDs to arrays of segment indices (e.g., $transUnits['id'] = [0, 1, 2]),
     * allowing trans-units with multiple segments (via <mrk> tags). For the help-id
     * attribute injection in tagOpen(), we only need the first segment's metadata.
     *
     * @return array<string, mixed>
     */
    protected function getCurrentSegment(): array
    {
        if ($this->currentTransUnitIsTranslatable !== 'no' && isset($this->transUnits[$this->currentTransUnitId])) {
            return $this->segments[$this->transUnits[$this->currentTransUnitId][0]];
        }

        return [];
    }

}
