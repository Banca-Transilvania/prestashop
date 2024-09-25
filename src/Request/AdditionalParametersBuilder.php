<?php

namespace BTiPay\Request;

use BTiPay\Helper\SubjectReader;

class AdditionalParametersBuilder implements BuilderInterface
{
    public function build(array $buildSubject)
    {
        $context = SubjectReader::readContext($buildSubject);

        if ($context->isMobileDevice()) {
            $data['pageView'] = 'MOBILE';
        }

        $data['jsonParams'] = '{"FORCE_3DS2":"true"}';

        return $data;
    }

}