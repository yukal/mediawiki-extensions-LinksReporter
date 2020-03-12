<?php

// use Wikimedia\Rdbms\DBQueryError;

class ApiLinksReporter extends ApiBase {

    /** @inheritDoc */
    public function execute() {
        $wgExtensionDirectory = $this->getConfig()->get('ExtensionDirectory');
        $cacheFile = $wgExtensionDirectory 
            . DIRECTORY_SEPARATOR . 'LinksReporter'
            . DIRECTORY_SEPARATOR . 'cache.json'
        ;

        $params = $this->extractRequestParams();
        $actType = $params[ 'type' ];
        $articleId = abs( intval( $params['aid'] ) );

        if ( !$actType ) {
            $this->dieWithError( [ 'linksreporter-noatype', $params['type'] ], 'noatype' );
        }

        $contents = file_get_contents( $cacheFile );
        $record = json_decode( $contents, true );

        if ( $actType == 'del' ) {
            unset( $record[ $articleId ] );
        }

        if ( $actType == 'add' ) {
            $title = Title::newFromID( $articleId );

            if ( is_null( $title ) ) {
                $this->dieWithError( [ 'linksreporter-noaid', $params['aid'] ], 'noaid' );
            }

            $record[ $title->getArticleID() ] = [ $title->getText(), $title->getFullURL() ];
        }

        $contents = json_encode( $record );

        if (file_put_contents($cacheFile, $contents)) {
            echo json_encode([ 'success' => true, 'aid' => $articleId ]);
        } else {
            echo json_encode([ 'success' => false ]);
        }
    }

    /** @inheritDoc */
    public function getAllowedParams() {
        return [
            'aid' => [
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
            ],
            'type' => [
                ApiBase::PARAM_TYPE => [ 'add', 'del' ],
                ApiBase::PARAM_ISMULTI => false,
                ApiBase::PARAM_REQUIRED => true,
            ],
            'token' => [
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
            ],
        ];
    }

    /** @inheritDoc */
    public function needsToken() {
        return 'csrf';
    }

    /** @inheritDoc */
    public function isWriteMode() {
        return true;
    }

    /** @inheritDoc */
    protected function getExamplesMessages() {
        return [
            'action=linksreporter&type=add&aid=142&token=123ABC'
                => 'apihelp-linksreporter-example-1',
        ];
    }
}
