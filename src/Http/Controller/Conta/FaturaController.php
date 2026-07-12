<?php

namespace App\Http\Controller\Conta;

use App\Shared\Service\RequestService;
use Symfony\Component\Routing\Annotation\Route;

#[Route(FaturaController::BASE_API)]
class FaturaController
{
    public function __construct(
        private readonly RequestService $requestService
    ){
    }

    public const BASE_API = '/api/fatura';

}
