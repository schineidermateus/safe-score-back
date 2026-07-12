<?php

namespace App\Http\Controller\Conta;

use App\Shared\Service\RequestService;
use Symfony\Component\Routing\Annotation\Route;

#[Route(CartaoCreditoController::BASE_API)]
class CartaoCreditoController
{
    public function __construct(
        private readonly RequestService $requestService
    ){
    }
    public const BASE_API = '/api/cartao-credito';

}
