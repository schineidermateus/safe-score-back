<?php

namespace App\Http\Controller\Conta;

use App\Domain\Conta\Entity\Conta;
use App\Domain\Conta\Enum\TipoConta;
use App\Domain\Conta\Service\ContaService;
use App\Http\DTO\Conta\ContaRequest;
use App\Shared\Service\RequestService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(ContaController::BASE_API)]
class ContaController
{
    public const BASE_API = '/api/conta';

    public function __construct(
        private readonly RequestService $requestService,
        private readonly ContaService $contaService
    ){
    }

    #[Route('', name: 'conta_create', methods: ['POST'])]
    public function criar(): Conta
    {
        $contaParams = ContaRequest::fromArray($this->requestService->getContent());

        if($contaParams->tipo === TipoConta::CARTAO_CREDITO) {
            throw new NotFoundHttpException('No route found for the requested URI and HTTP method.');
        }

        return $this->contaService->criarConta(
            $contaParams->nome,
            $contaParams->tipo,
            $contaParams->saldoInicial
        );
    }
}
