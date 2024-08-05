<?php

namespace Matecat\XliffParser\XliffReplacer;

use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class AbstractXliffReplacer {
    protected $originalFP;

    protected $tuTagName;                 // <trans-unit> (forXliff v 1.*) or <unit> (forXliff v 2.*)
    protected $inTU                = false;  // flag to check whether we are in a <trans-unit>
    protected $inTarget            = false;  // flag to check whether we are in a <target>, to ignore everything
    protected $isEmpty             = false;  // flag to check whether we are in an empty tag (<tag/>)
    protected $targetWasWritten    = false;  // flag to check is <target> was written in the current unit
    protected $segmentPositionInTu = -1;  // the current position of segment in the current <unit> (forXliff v 2.*)

    protected $CDATABuffer    = "";       // buffer for special tag
    protected $bufferIsActive = false;    // buffer for special tag

    protected     $offset                = 0;         // offset for SAX pointer
    protected     $outputFP;                  // output stream pointer
    protected     $currentBuffer;             // the current piece of text it's been parsed
    protected     $len;                       // length of the currentBuffer
    protected     $segments;                  // array of translations
    protected     $lastTransUnit         = [];
    protected int $segmentInUnitPosition = 0;
    protected     $currentTransUnitId;        // id of current <trans-unit>
    protected     $currentTransUnitIsTranslatable; // 'translate' attribute of current <trans-unit>
    protected     $unitContainsMda       = false;   // check if <unit> already contains a <mda:metadata> (forXliff v 2.*)
    protected     $hasWrittenCounts      = false;  // check if <unit> already wrote segment counts (forXliff v 2.*)

    protected $targetLang;

    protected $sourceInTarget;

    protected $transUnits;

    /** @var int */
    protected $xliffVersion;

    protected $callback;

    protected $logger;

    protected static $INTERNAL_TAG_PLACEHOLDER;

    protected $counts = [
            'raw_word_count' => 0,
            'eq_word_count'  => 0,
    ];

    /**
     * AbstractXliffReplacer constructor.
     *
     * @param string                              $originalXliffPath
     * @param int                                 $xliffVersion
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
            $segments,
            $transUnits,
            $trgLang,
            $outputFilePath,
            $setSourceInTarget,
            LoggerInterface $logger = null,
            XliffReplacerCallbackInterface $callback = null
    ) {
        self::$INTERNAL_TAG_PLACEHOLDER = $this->getInternalTagPlaceholder();
        $this->createOutputFileIfDoesNotExist( $outputFilePath );
        $this->setFileDescriptors( $originalXliffPath, $outputFilePath );
        $this->xliffVersion = $xliffVersion;
        $this->setTuTagName();
        $this->segments       = $segments;
        $this->targetLang     = $trgLang;
        $this->sourceInTarget = $setSourceInTarget;
        $this->transUnits     = $transUnits;
        $this->logger         = $logger;
        $this->callback       = $callback;
    }

    public function replaceTranslation() {
        fwrite( $this->outputFP, '<?xml version="1.0" encoding="UTF-8"?>' );

        //create Sax parser
        $xmlParser = $this->initSaxParser();

        while ( $this->currentBuffer = fread( $this->originalFP, 4096 ) ) {
            /*
               preprocess file
             */
            // obfuscate entities because sax automatically does html_entity_decode
            $temporary_check_buffer = preg_replace( "/&(.*?);/", self::$INTERNAL_TAG_PLACEHOLDER . '$1' . self::$INTERNAL_TAG_PLACEHOLDER, $this->currentBuffer );

            //avoid cutting entities in half:
            //the last fread could have truncated an entity (say, '&lt;' in '&l'), thus invalidating the escaping
            //***** and if there is an & that it is not an entity, this is an infinite loop !!!!!

            $escape_AMP = false;

            // 9 is the max length of an entity. So, suppose that the & is at the end of buffer,
            // add 9 Bytes and substitute the entities, if the & is present, and it is not at the end
            //it can't be an entity, exit the loop

            while ( true ) {
                $_ampPos = strpos( $temporary_check_buffer, '&' );

                //check for real entity or escape it to safely exit from the loop!!!
                if ( $_ampPos === false || strlen( substr( $temporary_check_buffer, $_ampPos ) ) > 9 ) {
                    $escape_AMP = true;
                    break;
                }

                //if an entity is still present, fetch some more and repeat the escaping
                $this->currentBuffer    .= fread( $this->originalFP, 9 );
                $temporary_check_buffer = preg_replace( "/&(.*?);/", self::$INTERNAL_TAG_PLACEHOLDER . '$1' . self::$INTERNAL_TAG_PLACEHOLDER, $this->currentBuffer );
            }

            //free stuff outside the loop
            unset( $temporary_check_buffer );

            $this->currentBuffer = preg_replace( "/&(.*?);/", self::$INTERNAL_TAG_PLACEHOLDER . '$1' . self::$INTERNAL_TAG_PLACEHOLDER, $this->currentBuffer );
            if ( $escape_AMP ) {
                $this->currentBuffer = str_replace( "&", self::$INTERNAL_TAG_PLACEHOLDER . 'amp' . self::$INTERNAL_TAG_PLACEHOLDER, $this->currentBuffer );
            }

            //get length of chunk
            $this->len = strlen( $this->currentBuffer );

            //parse chunk of text
            if ( !xml_parse( $xmlParser, $this->currentBuffer, feof( $this->originalFP ) ) ) {
                //if unable, raise an exception
                throw new RuntimeException( sprintf(
                        "XML error: %s at line %d",
                        xml_error_string( xml_get_error_code( $xmlParser ) ),
                        xml_get_current_line_number( $xmlParser )
                ) );
            }
            //get accumulated this->offset in document: as long as SAX pointer advances, we keep track of total bytes it has seen so far; this way, we can translate its global pointer in an address local to the current buffer of text to retrieve last char of tag
            $this->offset += $this->len;
        }

        // close Sax parser
        $this->closeSaxParser( $xmlParser );

    }

    protected function getLastCharacter( $parser ) {

        //this logic helps detecting empty tags
        //get current position of SAX pointer in all the stream of data is has read so far:
        //it points at the end of current tag
        $idx = xml_get_current_byte_index( $parser );

        //check whether the bounds of current tag are entirely in current buffer or the end of the current tag
        //is outside current buffer (in the latter case, it's in next buffer to be read by the while loop);
        //this check is necessary because we may have truncated a tag in half with current read,
        //and the other half may be encountered in the next buffer it will be passed
        if ( isset( $this->currentBuffer[ $idx - $this->offset ] ) ) {
            //if this tag entire lenght fitted in the buffer, the last char must be the last
            //symbol before the '>'; if it's an empty tag, it is assumed that it's a '/'
            $lastChar = $this->currentBuffer[ $idx - $this->offset ];
        } else {
            //if it's out, simple use the last character of the chunk
            $lastChar = $this->currentBuffer[ $this->len - 1 ];
        }

        return $lastChar;

    }

    /**
     * @return string
     */
    private function getInternalTagPlaceholder() {
        return "§" .
                substr(
                        str_replace(
                                [ '+', '/' ],
                                '',
                                base64_encode( openssl_random_pseudo_bytes( 10, $_crypto_strong ) )
                        ),
                        0,
                        4
                );
    }

    private function createOutputFileIfDoesNotExist( $outputFilePath ) {
        // create output file
        if ( !file_exists( $outputFilePath ) ) {
            touch( $outputFilePath );
        }
    }

    /**
     * @param $originalXliffPath
     * @param $outputFilePath
     */
    private function setFileDescriptors( $originalXliffPath, $outputFilePath ) {
        $this->outputFP = fopen( $outputFilePath, 'w+' );

        $streamArgs = null;

        if ( !( $this->originalFP = fopen( $originalXliffPath, "r", false, stream_context_create( $streamArgs ) ) ) ) {
            throw new RuntimeException( "could not open XML input" );
        }
    }

    /**
     * set tuTagName
     * <trans-unit> (xliff v1.*) || <unit> (xliff v2.*)
     */
    private function setTuTagName() {
        $this->tuTagName = ( $this->xliffVersion === 2 ) ? 'unit' : 'trans-unit';
    }

    /**
     * AbstractXliffReplacer destructor.
     */
    public function __destruct() {
        //this stream can be closed outside the class
        //to permit multiple concurrent downloads, so suppress warnings
        @fclose( $this->originalFP );
        fclose( $this->outputFP );
    }

    /**
     * Init Sax parser
     *
     * @return resource
     */
    protected function initSaxParser() {
        $xmlSaxParser = xml_parser_create( 'UTF-8' );
        xml_set_object( $xmlSaxParser, $this );
        xml_parser_set_option( $xmlSaxParser, XML_OPTION_CASE_FOLDING, false );
        xml_set_element_handler( $xmlSaxParser, 'tagOpen', 'tagClose' );
        xml_set_character_data_handler( $xmlSaxParser, 'characterData' );

        return $xmlSaxParser;
    }

    /**
     * @param resource $xmlSaxParser
     */
    protected function closeSaxParser( $xmlSaxParser ) {
        xml_parser_free( $xmlSaxParser );
    }

    /**
     * @param $parser
     * @param $name
     * @param $attr
     *
     * @return mixed
     */
    abstract protected function tagOpen( $parser, $name, $attr );

    /**
     * @param $parser
     * @param $name
     *
     * @return mixed
     */
    abstract protected function tagClose( $parser, $name );

    /**
     * @param $parser
     * @param $data
     *
     * @return mixed
     */
    protected function characterData( $parser, $data ): void {
        // don't write <target> data
        if ( !$this->inTarget && !$this->bufferIsActive ) {
            $this->postProcAndFlush( $this->outputFP, $data );
        } elseif ( $this->bufferIsActive ) {
            $this->CDATABuffer .= $data;
        }
    }

    /**
     * postprocess escaped data and write to disk
     *
     * @param resource $fp
     * @param string   $data
     * @param bool     $treatAsCDATA
     */
    protected function postProcAndFlush( $fp, $data, $treatAsCDATA = false ) {
        //postprocess string
        $data = preg_replace( "/" . self::$INTERNAL_TAG_PLACEHOLDER . '(.*?)' . self::$INTERNAL_TAG_PLACEHOLDER . "/", '&$1;', $data );
        $data = str_replace( '&nbsp;', ' ', $data );
        if ( !$treatAsCDATA ) {
            //unix2dos
            $data = str_replace( "\r\n", "\r", $data );
            $data = str_replace( "\n", "\r", $data );
            $data = str_replace( "\r", "\r\n", $data );
        }

        //flush to disk
        fwrite( $fp, $data );
    }

    protected function handleOpenUnit( string $name, array $attr ) {

        // check if we are entering into a <trans-unit> (xliff v1.*) or <unit> (xliff v2.*)
        if ( $this->tuTagName === $name ) {
            $this->inTU = true;

            // get id
            // trim to first 100 characters because this is the limit on Matecat's DB
            $this->currentTransUnitId = substr( $attr[ 'id' ], 0, 100 );

            // `translate` attribute can be only yes or no
            // current 'translate' attribute of the current trans-unit
            $this->currentTransUnitIsTranslatable = empty( $attr[ 'translate' ] ) ? 'yes' : $attr[ 'translate' ];

            $this->setLastTransUnitSegments();

        }
    }

    /**
     * @param array $seg
     */
    protected function updateSegmentCounts( array $seg = [] ) {

        $raw_word_count = $seg[ 'raw_word_count' ];
        $eq_word_count  = ( floor( $seg[ 'eq_word_count' ] * 100 ) / 100 );

        $this->counts[ 'segments_count_array' ][ $seg[ 'sid' ] ] = [
                'raw_word_count' => $raw_word_count,
                'eq_word_count'  => $eq_word_count,
        ];

        $this->counts[ 'raw_word_count' ] += $raw_word_count;
        $this->counts[ 'eq_word_count' ]  += $eq_word_count;
    }

    protected function resetCounts() {
        $this->counts[ 'segments_count_array' ] = [];
        $this->counts[ 'raw_word_count' ]       = 0;
        $this->counts[ 'eq_word_count' ]        = 0;
    }

    protected function checkForSelfClosedTagAndFlush( $parser, $tag ) {

        $lastChar = $this->getLastCharacter( $parser );

        //trim last space
        $tag = rtrim( $tag );

        //detect empty tag
        $this->isEmpty = $lastChar == '/';
        if ( $this->isEmpty ) {
            $tag .= $lastChar;
        }

        //add tag ending
        $tag .= ">";

        //set a Buffer for the segSource Source tag
        if ( $this->bufferIsActive ) { // we are opening a critical CDATA section
            //these are NOT source/seg-source/value empty tags, THERE IS A CONTENT, write it in buffer
            $this->CDATABuffer .= $tag;
        } else {
            $this->postProcAndFlush( $this->outputFP, $tag );
        }

    }

    /**
     * A trans-unit can contain a list of segments because of mrk tags
     * Copy the segment's list for this trans-unit in a different structure
     *
     * @return void
     */
    protected function setLastTransUnitSegments() {

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

        if ( !isset( $this->transUnits[ $this->currentTransUnitId ] ) ) {
            return;
        }

        $listOfSegmentsIds = $this->transUnits[ $this->currentTransUnitId ];
        $last_value        = null;
        $segmentsCount     = count( $listOfSegmentsIds );
        for ( $i = 0; $i < $segmentsCount; $i++ ) {
            $id = $listOfSegmentsIds[ $i ];
            if ( isset( $this->segments[ $id ] ) && ( $i == 0 || $last_value + 1 == $listOfSegmentsIds[ $i ] ) ) {
                $last_value            = $listOfSegmentsIds[ $i ];
                $this->lastTransUnit[] = $this->segments[ $id ];
            }
        }

    }

    /**
     * @return array
     */
    protected function getCurrentSegment(): array {
        if ( $this->currentTransUnitIsTranslatable !== 'no' && isset( $this->transUnits[ $this->currentTransUnitId ] ) ) {
            return $this->segments[ $this->segmentInUnitPosition ];
        }

        return [];
    }

}