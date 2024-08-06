<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 02/08/24
 * Time: 19:04
 *
 */

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Utils\Strings;

class XliffSdl extends Xliff12 {

    /**
     * @inheritDoc
     */
    protected function tagOpen( $parser, string $name, array $attr ) {

        $this->handleOpenUnit( $name, $attr );

        // check if we are entering into a <target>
        $this->checkSetInTarget( $name );

        // reset Marker positions
        if ( 'sdl:seg-defs' == $name ) {
            $this->segmentInUnitPosition = 0;
        }

        // open buffer
        $this->setInBuffer( $name );

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
                } else {
                    //normal tag flux, put attributes in it
                    // Warning, this is NOT an elseif
                    if ( $k != 'conf' ) {
                        //put also the current attribute in it if it is not a "conf" attribute
                        $tag .= "$k=\"$v\" ";
                    }
                }
            }

            $seg = $this->getCurrentSegment();

            if ( 'sdl:seg' == $name && !empty( $seg ) and isset( $seg[ 'sid' ] ) ) {
                $tag .= $this->prepareTargetStatuses( $seg );
            }

            $this->checkForSelfClosedTagAndFlush( $parser, $tag );

        }

    }

    /**
     * @param $segment
     *
     * @return string
     */
    protected function prepareTargetStatuses( $segment ): string {
        $statusMap = [
                'NEW'        => '',
                'DRAFT'      => 'Draft',
                'TRANSLATED' => 'Translated',
                'APPROVED'   => 'ApprovedTranslation',
                'APPROVED2'  => 'ApprovedSignOff',
                'REJECTED'   => 'RejectedTranslation',
        ];

        return "conf=\"{$statusMap[ $segment[ 'status' ] ]}\" ";
    }

    protected function rebuildMarks( array $seg, string $translation ): string {

        $trailingSpaces = str_repeat( ' ', Strings::getTheNumberOfTrailingSpaces( $translation ) );

        if ( $seg[ 'mrk_id' ] !== null && $seg[ 'mrk_id' ] != '' ) {
            if ( $this->targetLang === 'ja-JP' ) {
                $seg[ 'mrk_succ_tags' ] = ltrim( $seg[ 'mrk_succ_tags' ] );
            }

            $translation = "<mrk mid=\"" . $seg[ 'mrk_id' ] . "\" mtype=\"seg\">" . $seg[ 'mrk_prev_tags' ] . rtrim( $translation ) . $seg[ 'mrk_succ_tags' ] . "</mrk>" . $trailingSpaces;
        }

        return $translation;

    }

}