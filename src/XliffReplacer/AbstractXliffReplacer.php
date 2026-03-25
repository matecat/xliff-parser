<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Exception\FileOpenException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use XMLParser;

abstract class AbstractXliffReplacer
{

    /** @var resource|null */
    protected $originalFP = null;

    /** @var resource|null */
    protected $outputFP = null;

    protected string $tuTagName;                 // <trans-unit> (forXliff v 1.*) or <unit> (forXliff v 2.*)
    protected bool $inTU = false;  // flag to check whether we are in a <trans-unit>
    protected bool $inTarget = false;  // flag to check whether we are in a <target>, to ignore everything
    protected bool $inAltTrans = false;  // flag to check whether we are in an <alt-trans> (xliff 1.2) or <mtc:matches> (xliff 2.0)
    protected string $alternativeMatchesTag = ""; // polymorphic tag name for xliff 1.2 and 2.0
    protected bool $isEmpty = false;  // flag to check whether we are in an empty tag (<tag/>)
    protected bool $targetWasWritten = false;  // flag to check is <target> was written in the current unit
    protected string $CDATABuffer = "";       // buffer for special tag
    protected string $namespace = "";       // Custom namespace
    protected bool $bufferIsActive = false;    // flag for buffeting

    protected int $offset = 0;         // offset for SAX pointer

    protected string $currentBuffer = '';         // the current piece of text it's been parsed
    protected int $len = 0;                   // length of the currentBuffer

    /** @var array<int|string, array<string, mixed>> */
    protected array $segments;                  // array of translations

    /** @var array<int, array<string, mixed>> */
    protected array $lastTransUnit = [];

    protected int $segmentInUnitPosition = 0;
    protected ?string $currentTransUnitId = null;        // id of current <trans-unit>
    protected ?string $currentTransUnitIsTranslatable = null; // 'translate' attribute of current <trans-unit>
    protected bool $hasWrittenCounts = false;  // check if <unit> already wrote segment counts (forXliff v 2.*)
    protected string $targetLang;
    protected bool $sourceInTarget = false;

    /** @var array<int, string> */
    protected array $nodesToBuffer = [];

    /** @var array<int|string, array<int, int>> */
    protected array $transUnits;

    protected int $xliffVersion;

    protected ?XliffReplacerCallbackInterface $callback;

    protected ?LoggerInterface $logger;

    protected static string $internalTagPlaceholder;

    /** @var array{raw_word_count: int|float, eq_word_count: int|float, segments_count_array?: array<string, array{raw_word_count: int|float, eq_word_count: float}>} */
    protected array $counts = [
        'raw_word_count' => 0,
        'eq_word_count' => 0,
    ];

    /**
     * AbstractXliffReplacer constructor.
     *
     * @param string $originalXliffPath Path to original XLIFF file
     * @param int $xliffVersion XLIFF version (1 or 2)
     * @param array<int|string, array<string, mixed>> $segments Array of translation segments
     * @param array<int|string, array<int, int>> $transUnits Trans-unit mapping
     * @param string $trgLang Target language code
     * @param string $outputFilePath Path for output file
     * @param bool $setSourceInTarget Whether to copy source to target
     * @param LoggerInterface|null $logger Optional logger
     * @param XliffReplacerCallbackInterface|null $callback Optional callback for error handling
     */
    public function __construct(
        string $originalXliffPath,
        int $xliffVersion,
        array $segments,
        array $transUnits,
        string $trgLang,
        string $outputFilePath,
        bool $setSourceInTarget,
        ?LoggerInterface $logger = null,
        ?XliffReplacerCallbackInterface $callback = null
    ) {
        self::$internalTagPlaceholder = $this->getInternalTagPlaceholder();
        $this->createOutputFileIfDoesNotExist($outputFilePath);
        $this->setFileDescriptors($originalXliffPath, $outputFilePath);
        $this->xliffVersion = $xliffVersion;
        $this->segments = $segments;
        $this->targetLang = $trgLang;
        $this->sourceInTarget = $setSourceInTarget;
        $this->transUnits = $transUnits;
        $this->logger = $logger;
        $this->callback = $callback;
    }

    /**
     * Replace translations in the XLIFF file.
     */
    public function replaceTranslation(): void
    {
        fwrite($this->outputFP, '<?xml version="1.0" encoding="UTF-8"?>');

        //create Sax parser
        $xmlParser = $this->initSaxParser();

        while (($buffer = fread($this->originalFP, 4096)) !== false && $buffer !== '') {
            $this->currentBuffer = $buffer;
            /*
               preprocess file
             */
            // obfuscate entities because sax automatically does html_entity_decode
            $temporary_check_buffer = preg_replace(
                "/&(.*?);/",
                self::$internalTagPlaceholder . '$1' . self::$internalTagPlaceholder,
                $this->currentBuffer
            );

            // Prevent splitting HTML/XML entities across buffer boundaries during file reading.
            // An entity like `&lt;`, could be cut midway (e.g., '&l'), breaking the entity escaping.
            // Max entity length is 9 chars (e.g., '&thetasym;'), so we check up to 9 bytes ahead.
            while (true) {
                // Find the position of the last '&' character in the temporary buffer
                $_ampPos = strpos($temporary_check_buffer, '&');

                // If no '&' exists, or if there's enough data after '&' (>9 chars),
                // it's either not an entity or complete - safe to exit
                if ($_ampPos === false || strlen(substr($temporary_check_buffer, $_ampPos)) > 9) {
                    break;
                }

                // Read 9 more bytes to ensure we have the complete entity
                $extraBuffer = fread($this->originalFP, 9);
                if ($extraBuffer !== false) {
                    $this->currentBuffer .= $extraBuffer;
                }

                // Replace entities (&...;) with placeholder-wrapped content for safe processing
                $temporary_check_buffer = preg_replace(
                    "/&(.*?);/",
                    self::$internalTagPlaceholder . '$1' . self::$internalTagPlaceholder,
                    $this->currentBuffer
                );
            }

            //free stuff outside the loop
            unset($temporary_check_buffer);

            $this->currentBuffer = preg_replace(
                "/&(.*?);/",
                self::$internalTagPlaceholder . '$1' . self::$internalTagPlaceholder,
                $this->currentBuffer
            );
            $this->currentBuffer = str_replace(
                "&",
                self::$internalTagPlaceholder . 'amp' . self::$internalTagPlaceholder,
                $this->currentBuffer
            );

            //get length of chunk
            $this->len = strlen($this->currentBuffer);

            /*
            * Get the accumulated this->offset in the document:
             * as long as SAX pointer advances, we keep track of total bytes it has seen so far;
             * this way, we can translate its global pointer in an address local to the current buffer of text to retrieve the last char of tag
            */
            $this->offset += $this->len;

            //parse chunk of text
            $this->runParser($xmlParser);
        }

        // close Sax parser
        $this->closeSaxParser($xmlParser);
    }

    /**
     * Run the XML parser on the current buffer.
     */
    protected function runParser(XMLParser $xmlParser): void
    {
        //parse chunk of text
        if (!xml_parse($xmlParser, $this->currentBuffer, feof($this->originalFP))) {
            //if unable, raise an exception
            throw new RuntimeException(
                sprintf(
                    "XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($xmlParser)),
                    xml_get_current_line_number($xmlParser)
                )
            );
        }
    }

    /**
     * Get the last character from the current buffer based on parser position.
     */
    protected function getLastCharacter(XMLParser $parser): string
    {
        //this logic helps detecting empty tags
        //get current position of SAX pointer in all the stream of data is has read so far:
        //it points at the end of current tag
        $idx = xml_get_current_byte_index($parser);

        //check whether the bounds of current tag are entirely in current buffer or the end of the current tag
        //is outside current buffer (in the latter case, it's in next buffer to be read by the while loop);
        //this check is necessary because we may have truncated a tag in half with current read,
        //and the other half may be encountered in the next buffer it will be passed
        return $this->currentBuffer[$idx - $this->offset] ?? $this->currentBuffer[$this->len - 1];
    }

    /**
     * @return string
     */
    private function getInternalTagPlaceholder(): string
    {
        return "§" .
            substr(
                str_replace(
                    ['+', '/'],
                    '',
                    base64_encode(openssl_random_pseudo_bytes(10))
                ),
                0,
                4
            );
    }

    /**
     * Create output file if it does not exist.
     */
    private function createOutputFileIfDoesNotExist(string $outputFilePath): void
    {
        // create output file
        if (!file_exists($outputFilePath)) {
            @touch($outputFilePath);
        }
    }

    /**
     * Set file descriptors for input and output files.
     */
    private function setFileDescriptors(string $originalXliffPath, string $outputFilePath): void
    {
        $outputFP = @fopen($outputFilePath, 'w+');
        if ($outputFP === false) {
            throw new FileOpenException("could not open output file: $outputFilePath");
        }
        $this->outputFP = $outputFP;

        $streamArgs = null;
        $originalFP = @fopen($originalXliffPath, "r", false, stream_context_create($streamArgs));
        if ($originalFP === false) {
            throw new FileOpenException("could not open XML input: $originalXliffPath");
        }
        $this->originalFP = $originalFP;
    }

    /**
     * AbstractXliffReplacer destructor.
     */
    public function __destruct()
    {
        //this stream can be closed outside the class
        //to permit multiple concurrent downloads, so suppress warnings
        if (is_resource($this->originalFP)) {
            fclose($this->originalFP);
        }

        if (is_resource($this->outputFP)) {
            fclose($this->outputFP);
        }
    }

    /**
     * Initialize SAX XML parser.
     */
    protected function initSaxParser(): XMLParser
    {
        $xmlSaxParser = xml_parser_create('UTF-8');
        xml_set_object($xmlSaxParser, $this);
        xml_parser_set_option($xmlSaxParser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($xmlSaxParser, 'tagOpen', 'tagClose');
        xml_set_character_data_handler($xmlSaxParser, 'characterData');

        return $xmlSaxParser;
    }

    /**
     * Close SAX XML parser and free resources.
     */
    protected function closeSaxParser(XMLParser $xmlSaxParser): void
    {
        xml_parser_free($xmlSaxParser);
    }

    /**
     * Handle opening XML tags.
     *
     * @param XMLParser $parser The XML parser
     * @param string $name Tag name
     * @param array<string, string> $attr Tag attributes
     */
    abstract protected function tagOpen(XMLParser $parser, string $name, array $attr): void;

    /**
     * Handle closing XML tags.
     *
     * @param XMLParser $parser The XML parser
     * @param string $name Tag name
     */
    abstract protected function tagClose(XMLParser $parser, string $name): void;

    /**
     * Handle character data within XML elements.
     *
     * @param XMLParser $parser The XML parser
     * @param string $data Character data
     */
    protected function characterData(XMLParser $parser, string $data): void
    {
        // don't write <target> data
        if (!$this->inTarget && !$this->bufferIsActive) {
            $this->postProcAndFlush($this->outputFP, $data);
        } elseif ($this->bufferIsActive) {
            $this->CDATABuffer .= $data;
        }
    }

    /**
     * Postprocess escaped data and write to disk.
     *
     * @param resource $fp File pointer
     * @param string $data Data to write
     * @param bool $treatAsCDATA Whether to treat as CDATA
     */
    protected function postProcAndFlush($fp, string $data, bool $treatAsCDATA = false): void
    {
        //postprocess string
        $data = preg_replace(
            "/" . self::$internalTagPlaceholder . '(.*?)' . self::$internalTagPlaceholder . "/",
            '&$1;',
            $data
        );
        $data = str_replace('&nbsp;', ' ', $data);
        if (!$treatAsCDATA) {
            //unix2dos
            $data = str_replace("\r\n", "\r", $data);
            $data = str_replace("\n", "\r", $data);
            $data = str_replace("\r", "\r\n", $data);
        }

        //flush to disk
        fwrite($fp, $data);
    }

    /**
     * Handle opening of a trans-unit or unit tag.
     *
     * @param string $name Tag name
     * @param array<string, string> $attr Tag attributes
     */
    protected function handleOpenUnit(string $name, array $attr): void
    {
        // check if we are entering into a <trans-unit> (xliff v1.*) or <unit> (xliff v2.*)
        if ($this->tuTagName === $name) {
            $this->inTU = true;

            // get id
            // trim to first 100 characters because this is the limit on Matecat's DB
            $this->currentTransUnitId = substr($attr['id'], 0, 100);

            // `translate` attribute can be only yes or no
            // current 'translate' attribute of the current trans-unit
            $this->currentTransUnitIsTranslatable = empty($attr['translate']) ? 'yes' : $attr['translate'];

            $this->setLastTransUnitSegments();
        }
    }

    /**
     * Handle opening xliff tag and add namespace.
     *
     * @param string $name Tag name
     * @param array<string, string> $attr Tag attributes
     * @param string $tag Current tag string being built
     *
     * @return string Modified tag string
     */
    protected function handleOpenXliffTag(string $name, array $attr, string $tag): string
    {
        // Add MateCat specific namespace.
        // Add trgLang
        if ($name === 'xliff') {
            if (!array_key_exists('xmlns:' . $this->namespace, $attr)) {
                $tag .= ' xmlns:' . $this->namespace . '="https://www.matecat.com" ';
            }
            $tag = preg_replace('/trgLang="(.*?)"/', 'trgLang="' . $this->targetLang . '"', $tag);
        }

        return $tag;
    }

    /**
     * Check if entering a target tag and set flag.
     */
    protected function checkSetInTarget(string $name): void
    {
        // check if we are entering into a <target>
        if ('target' === $name && !$this->inAltTrans) {
            if ($this->currentTransUnitIsTranslatable === 'no') {
                $this->inTarget = false;
            } else {
                $this->inTarget = true;
            }
        }
    }

    /**
     * Try to set alt-trans flag.
     */
    protected function trySetAltTrans(string $name): void
    {
        $this->inAltTrans = $this->inAltTrans || $this->alternativeMatchesTag === $name;
    }

    /**
     * Try to unset alt-trans flag.
     */
    protected function tryUnsetAltTrans(string $name): void
    {
        if ($this->alternativeMatchesTag === $name) {
            $this->inAltTrans = false;
        }
    }

    /**
     * Set buffer active for specific node types.
     */
    protected function setInBuffer(string $name): void
    {
        if (in_array($name, $this->nodesToBuffer)) {
            $this->bufferIsActive = true;
        }

        // We need bufferIsActive for <target> nodes with currentTransUnitIsTranslatable = 'NO'
        // because in the other case, the target can be chunked into pieces by xml_set_character_data_handler()
        // and this can potentially lead to a wrong string rebuild by postProcAndFlush function if the internal placeholders are split
        if ($name === 'target' && $this->currentTransUnitIsTranslatable === 'no') {
            $this->bufferIsActive = true;
        }
    }

    /**
     * Update segment word counts.
     *
     * @param array<string, mixed> $seg Segment data
     */
    protected function updateSegmentCounts(array $seg = []): void
    {
        $raw_word_count = $seg['raw_word_count'];
        $eq_word_count = (floor($seg['eq_word_count'] * 100) / 100);

        $this->counts['segments_count_array'][$seg['sid']] = [
            'raw_word_count' => $raw_word_count,
            'eq_word_count' => $eq_word_count,
        ];

        $this->counts['raw_word_count'] += $raw_word_count;
        $this->counts['eq_word_count'] += $eq_word_count;
    }

    /**
     * Reset word counts.
     */
    protected function resetCounts(): void
    {
        $this->counts['segments_count_array'] = [];
        $this->counts['raw_word_count'] = 0;
        $this->counts['eq_word_count'] = 0;
    }

    /**
     * Check for self-closed tags and flush to output.
     */
    protected function checkForSelfClosedTagAndFlush(XMLParser $parser, string $tag): void
    {
        $lastChar = $this->getLastCharacter($parser);

        //trim last space
        $tag = rtrim($tag);

        //detect empty tag
        $this->isEmpty = $lastChar == '/';
        if ($this->isEmpty) {
            $tag .= $lastChar;
        }

        //add tag ending
        $tag .= ">";

        //set a Buffer for the segSource Source tag
        if ($this->bufferIsActive) { // we are opening a critical CDATA section
            //these are NOT source/seg-source/value empty tags, THERE IS A CONTENT, write it in buffer
            $this->CDATABuffer .= $tag;
        } else {
            $this->postProcAndFlush($this->outputFP, $tag);
        }
    }

    /**
     * A trans-unit can contain a list of segments because of mrk tags.
     * Copy the segment's list for this trans-unit in a different structure.
     */
    protected function setLastTransUnitSegments(): void
    {
        /*
         * At the end of every cycle the segment grouping information is lost: unset( 'matecat|' . $this->currentId )
         *
         * We need to take the info about the last segment parsed
         *          ( normally more than 1 db row because of mrk tags )
         *
         * So, copy the current segment data group into another structure to keep the last segment
         * for the next tagOpen ( possible sdl:seg-defs )
         *
         */
        $this->lastTransUnit = [];

        if (!isset($this->transUnits[$this->currentTransUnitId])) {
            return;
        }

        $listOfSegmentsIds = $this->transUnits[$this->currentTransUnitId];
        $last_value = null;
        $segmentsCount = count($listOfSegmentsIds);
        for ($i = 0; $i < $segmentsCount; $i++) {
            $id = $listOfSegmentsIds[$i];
            if (isset($this->segments[$id]) && ($i === 0 || $last_value + 1 === $listOfSegmentsIds[$i])) {
                $last_value = $listOfSegmentsIds[$i];
                $this->lastTransUnit[] = $this->segments[$id];
            }
        }
    }

    /**
     * Get current segment data.
     *
     * @return array<string, mixed>
     */
    protected function getCurrentSegment(): array
    {
        if (
            $this->currentTransUnitIsTranslatable !== 'no' &&
            isset($this->transUnits[$this->currentTransUnitId]) &&
            isset($this->segments[$this->segmentInUnitPosition])
        ) {
            return $this->segments[$this->segmentInUnitPosition];
        }

        return [];
    }
}