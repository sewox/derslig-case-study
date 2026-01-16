<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "Derslig Case Study API",
    title: "Derslig Wallet API",
    contact: new OA\Contact(email: "admin@derslig.com"),
    license: new OA\License(name: "Apache 2.0", url: "http://www.apache.org/licenses/LICENSE-2.0.html")
)]
class SwaggerInfo
{
    #[OA\Get(
        path: "/",
        description: "Home page",
        responses: [
            new OA\Response(response: "200", description: "Welcome")
        ]
    )]
    public function index() {}
}
