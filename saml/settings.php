<?php

return [
    'strict' => true, //true
    'debug'  => false,

    'proxyVars' => true,

    'baseurl' => 'https://devdocs.ucuenca.edu.ec',

    'sp' => [
        'entityId' => 'https://devdocs.ucuenca.edu.ec/saml/metadata.php',
        'assertionConsumerService' => [
            'url' => 'https://devdocs.ucuenca.edu.ec/saml/accs.php',
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ],
        'singleLogoutService' => [
            'url' => 'https://devdocs.ucuenca.edu.ec/saml/sls.php',
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'x509cert'   => trim(file_get_contents(__DIR__.'/certs/sp.crt')),
	'privateKey' => trim(file_get_contents(__DIR__.'/certs/sp.key')),
	// 'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
    ],

    'idp' => [
        'entityId' => 'http://ADFS-PRUE.ucuenca.edu.ec/adfs/services/trust',
        'singleSignOnService' => [
            'url' => 'https://adfs-prue.ucuenca.edu.ec/adfs/ls/',
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'singleLogoutService' => [
            'url' => 'https://adfs-prue.ucuenca.edu.ec/adfs/ls/',
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'x509cert' => <<< 'EOT'
-----BEGIN CERTIFICATE-----
MIIE7DCCAtSgAwIBAgIQFjuYdlbzeLJMUJX9Qify0TANBgkqhkiG9w0BAQsFADAy
MTAwLgYDVQQDEydBREZTIFNpZ25pbmcgLSBBREZTLVBSVUUudWN1ZW5jYS5lZHUu
ZWMwHhcNMjUwNjA2MTUzNjI0WhcNMjYwNjA2MTUzNjI0WjAyMTAwLgYDVQQDEydB
REZTIFNpZ25pbmcgLSBBREZTLVBSVUUudWN1ZW5jYS5lZHUuZWMwggIiMA0GCSqG
SIb3DQEBAQUAA4ICDwAwggIKAoICAQCb68QF/+/ZCsGRV9qvDoy3Mrhjv3cFllUw
WfztmCxq09hqzd7UzzkdN9+Cx+IidKzIUWaMGJmuceYiCFkC7caOWOoI2UDz6Z+5
vf/5BOYj2NOLh19ppX7bIoLMOAaskzk/njR3ltXaXA1XcusBpNKhBdPPJdZLijYj
UrVMd1SwBhCeP2AshDnIm6Ye2XCNeewH9aklRJqvMsa8qrDA5i5B/2HzvMddN00j
0vTFsKPA+l7iqGue1kjg37Ngx1ctwQIBw0/LGShI6RiC4meJz5lwUYdfWQoSpl+r
KfFdC3QwSoe+wbzEbOHnfnIDc7UV112hbvQOkxNHZtWf/dTKaB9IOiys446f4dwM
dwTBmdlfBJ+R7k7ohTLFZE1hVscZ6+bIhpzSzBD0jw9lE7kiLaUnnlyUpr5ZVNxH
F2VBjCVgGox672brIXX9LcHypiN/pOvjDjCMNFBHMjrnrom2Bjsi6fqk3K+VERle
EnrjYoUySgVw+rBX3bit29DisQofC9+lQzHmpkCo+OJoEKw5SfAr1P3ZzNnCVkwU
VqzW8PzivToA86A5dnmUOqFDcSn4KYTxgE9ZvBeMviTvsRoEbeBTNibqfxPP4sGu
Bl2DOdiV/iP7t4pW55vM8/H1nwzp7p8ZNowPlJCCyhlsTWCro1nxSKaNDYw/S6GM
2/brn2p6sQIDAQABMA0GCSqGSIb3DQEBCwUAA4ICAQBjehmfEKSR1prXRYBUrJzX
EV50d2ph4FgTHi8ejLlE3c7ELTA6cqJ7KLnbozhXA+wtAIhkclvRVevpW9VkRhJA
gYCXEKqClfBABQRcnI8TAzOwUcVg+2hUA/kkGGvXVOJLuIsloGXpfc5TxT8OG26T
nGvMW5WiPAFImxraFx5xrICBkTKcnp1ebb7nVwQMO6A7MuymuyMHmxyR1UN8zdPL
GOBO91LjBMCi+4D1FPQ8DLTzmhY1gq2g3Kjj3zFP5SXZwfmEMdqO9RCm6O0z6Hqk
iTiUY8bewJiMRL5xbCWyEp5AFU1lX6NSlPxBRl8uFDucrsSyJZvjfLzgjKf7gW3V
1LWrL6rB6SDOms1ne3Ms0T/DNoWfG6QKZgNDXjfpe/iKL1/YX13vyqPianEFON+B
iF3NK6Q1weuQKSEIOflD8Qqom9J6f47SAad4bjQ31dB1ulj78akrCvlpr8nDLW32
ylcYlKGFUtmG0mxGNLPeS2EP4hYbvG1ePb6TycV3yDGq6e7qJexk1B1i4ZiAFYAv
VbaX6GMWq+C4AfPWv5R3v/9Zz+x0+xYGBcPyNkltDJBFkG5c9VkypX9CnA2EuNWg
A1XeqZIT7b95IKMjp+ZkI6HXvUBFr2IcSj1wTzICmheHRd0t6YKJjccHBfsHRQax
vIcNZhFJxnAJCJpOu3p9xw==
-----END CERTIFICATE-----
EOT
    ],

    'security' => [
        'authnRequestsSigned'    => true,
        'logoutRequestSigned'    => true,
        'logoutResponseSigned'   => true,
        'wantAssertionsSigned'   => true,
        'wantMessagesSigned'     => false, //true
        'signatureAlgorithm'     => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
        'digestAlgorithm'        => 'http://www.w3.org/2001/04/xmlenc#sha256',
        'requestedAuthnContext'  => false,
    ],

    'contactPerson' => [
        'technical' => [
            'givenName'    => 'Soporte TI',
            'emailAddress' => 'soporte@ucuenca.edu.ec',
        ],
    ],
    'organization' => [
        'es' => [
            'name'        => 'Universidad de Cuenca',
            'displayname' => 'Universidad de Cuenca',
            'url'         => 'https://www.ucuenca.edu.ec',
        ],
    ],
];

