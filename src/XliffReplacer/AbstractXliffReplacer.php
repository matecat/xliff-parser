<?php

namespace Matecat\XliffParser\XliffReplacer;

use Psr\Log\LoggerInterface;

abstract class AbstractXliffReplacer
{
    protected $originalFP;

    protected $tuTagName;                // <trans-unit> (forXliff v 1.*) or <unit> (forXliff v 2.*)
    protected $inTU     = false;         // flag to check whether we are in a <trans-unit>
    protected $inTarget = false;         // flag to check whether we are in a <target>, to ignore everything
    protected $isEmpty  = false;         // flag to check whether we are in an empty tag (<tag/>)

    protected $CDATABuffer    = "";      // buffer for special tag
    protected $bufferIsActive = false;   // buffer for special tag

    protected $offset        = 0;        // offset for SAX pointer
    protected $outputFP;                 // output stream pointer
    protected $currentBuffer;            // the current piece of text it's been parsed
    protected $len;                      // length of the currentBuffer
    protected $segments;                 // array of translations
    protected $lastTransUnit = [];
    protected $currentTransUnitId;       // id of current <trans-unit>
    protected $currentSegmentArray = []; // id of current <segment> (forXliff v 2.*)

    protected $targetLang;

    protected $sourceInTarget;

    protected $transUnits;

    protected $xliffVersion;

    protected $callback;

    protected $logger;

    protected static $INTERNAL_TAG_PLACEHOLDER;

    protected $counts = [
            'raw_word_count' => 0,
            'eq_word_count' => 0,
    ];

    /**
     * AbstractXliffReplacer constructor.
     *
     * @param string                              $originalXliffPath
     * @param string                              $xliffVersion
     * @param array                               $segments
     * @param array                               $transUnits
     * @param string                              $trgLang
     * @param string                              $outputFilePath
     * @param bool                                $setSourceInTarget
     * @param LoggerInterface|null                $logger
     * @param XliffReplacerCallbackInterface|null $callback
     */
    public function __construct(
            $originalXliffPath,
            $xliffVersion,
            &$segments,
            &$transUnits,
            $trgLang,
            $outputFilePath,
            $setSourceInTarget,
            LoggerInterface $logger = null,
            XliffReplacerCallbackInterface $callback = null
    )
    {
        self::$INTERNAL_TAG_PLACEHOLDER = $this->getInternalTagPlaceholder();
        $this->createOutputFileIfDoesNotExist($outputFilePath);
        $this->setFileDescriptors($originalXliffPath, $outputFilePath);
        $this->xliffVersion   = $xliffVersion;
        $this->setTuTagName();
        $this->segments       = $segments;
        $this->targetLang     = $trgLang;
        $this->sourceInTarget = $setSourceInTarget;
        $this->transUnits     = $transUnits;
        $this->logger         = $logger;
        $this->callback       = $callback;
    }

    /**
     * @return string
     */
    private function getInternalTagPlaceholder()
    {
        return "§" .
                substr(
                    str_replace(
                        [ '+', '/' ],
                        '',
                        base64_encode(openssl_random_pseudo_bytes(10, $_crypto_strong))
                    ),
                    0,
                    4
                );
    }

    private function createOutputFileIfDoesNotExist($outputFilePath)
    {
        // create output file
        if (!file_exists($outputFilePath)) {
            touch($outputFilePath);
        }
    }

    /**
     * @param $originalXliffPath
     * @param $outputFilePath
     */
    private function setFileDescriptors($originalXliffPath, $outputFilePath)
    {
        $this->outputFP = fopen($outputFilePath, 'w+');

        // setting $this->originalFP
        $streamArgs = null;

        if (!($this->originalFP = fopen($originalXliffPath, "r", false, stream_context_create($streamArgs)))) {
            die("could not open XML input");
        }
    }

    /**
     * set tuTagName
     * <trans-unit> (xliff v1.*) || <unit> (xliff v2.*)
     */
    private function setTuTagName()
    {
        $this->tuTagName = ($this->xliffVersion === 2) ? 'unit': 'trans-unit';
    }

    /**
     * AbstractXliffReplacer destructor.
     */
    public function __destruct()
    {
        //this stream can be closed outside the class
        //to permit multiple concurrent downloads, so suppress warnings
        @fclose($this->originalFP);
        fclose($this->outputFP);
    }

    abstract public function replaceTranslation();

    /**
     * Init Sax parser
     */
    protected function initSaxParser()
    {
        $xmlSaxParser = xml_parser_create('UTF-8');
        xml_set_object($xmlSaxParser, $this);
        xml_parser_set_option($xmlSaxParser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($xmlSaxParser, 'tagOpen', 'tagClose');
        xml_set_character_data_handler($xmlSaxParser, 'characterData');

        return $xmlSaxParser;
    }

    /**
     * @param resource $xmlSaxParser
     */
    protected function closeSaxParser($xmlSaxParser)
    {
        xml_parser_free($xmlSaxParser);
    }

    /**
     * @param $parser
     * @param $name
     * @param $attr
     *
     * @return mixed
     */
    abstract protected function tagOpen($parser, $name, $attr);

    /**
     * @param $parser
     * @param $name
     *
     * @return mixed
     */
    abstract protected function tagClose($parser, $name);

    /**
     * @param $parser
     * @param $data
     *
     * @return mixed
     */
    abstract protected function characterData($parser, $data);

    /**
     * postprocess escaped data and write to disk
     *
     * @param resource $fp
     * @param string $data
     * @param bool $treatAsCDATA
     */
    protected function postProcAndFlush($fp, $data, $treatAsCDATA = false)
    {
        //postprocess string
        $data = preg_replace("/" . self::$INTERNAL_TAG_PLACEHOLDER . '(.*?)' . self::$INTERNAL_TAG_PLACEHOLDER . "/", '&$1;', $data);
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
}
