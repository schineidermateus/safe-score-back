<?php

namespace App\Http\Controller\Transacao;

use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Domain\Conta\Repository\ContaRepository;
use App\Domain\Transacao\Entity\Transacao;
use App\Domain\Transacao\Repository\TransacaoRepository;
use App\Domain\Transacao\Service\TransacaoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/transacoes')]
class TransacaoController
{
    public function __construct(
        private readonly TransacaoService $service,
        private readonly TransacaoRepository $repo,
        private readonly ContaRepository $contaRepository,
    ) {}

    #[Route('', name: 'transacao_criar', methods: ['POST'])]
    public function criar(Request $req, ValidatorInterface $validator): Transacao
    {
        $data = json_decode($req->getContent(), true);

        $transacao = new Transacao();
        $transacao->setValor($data['valor'] ?? null);
        $transacao->setTipoTransacao($data['tipoTransacao'] ?? null);
        $transacao->setDescricao($data['descricao'] ?? null);
        $transacao->setDataVencimento(new \DateTime($data['dataVencimento']));

        $conta = $this->contaRepository->find($data['contaId']);
        if (!$conta) {
            throw new NotFoundHttpException('Conta não encontrada');
        }

        $transacao->setConta($conta);

        // validação automática
        $errors = $validator->validate($transacao);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        return $this->service->criar($transacao);
    }

    #[Route('/transferencia', name: 'transacao_transferencia', methods: ['POST'])]
    public function transferencia(Request $req): array
    {
        $data = json_decode($req->getContent(), true);

        $origem = $this->contaRepository->find($data['contaOrigemId']);
        $destino = $this->contaRepository->find($data['contaDestinoId']);

        if (!$origem || !$destino) {
            throw new NotFoundHttpException('Conta origem ou destino inválida');
        }

        $vencimento = new \DateTime($data['dataVencimento']);

        [$saida, $entrada] = $this->service->criarTransferencia(
            origem: $origem,
            destino: $destino,
            valor: $data['valor'],
            vencimento: $vencimento,
            descricao: $data['descricao'] ?? null
        );

        return [
            'saida' => $saida,
            'entrada' => $entrada
        ];
    }

    #[Route('/{id}/liquidar', name: 'transacao_liquidar', methods: ['POST'])]
    public function liquidar(int $id, Request $req): Transacao
    {
        $data = json_decode($req->getContent(), true);

        $transacao = $this->repo->find($id);
        if (!$transacao) {
            throw new NotFoundHttpException('Transação não encontrada');
        }

        $valor = $data['valor'];
        $dataCaixa = new \DateTime($data['dataCaixa']);

        return $this->service->liquidar($transacao, $valor, $dataCaixa);
    }

    #[Route('/abertas/{contaId}', name: 'transacao_abertas', methods: ['GET'])]
    public function abertas(int $contaId): array
    {
        $conta = $this->contaRepository->find($contaId);
        if (!$conta) {
            throw new NotFoundHttpException('Conta não encontrada');
        }

        return $this->service->buscarAbertasPorConta($conta);
    }

    #[Route('/atrasadas', name: 'transacao_atrasadas', methods: ['GET'])]
    public function atrasadas(): array
    {
        return $this->service->buscarAtrasadas();
    }

    #[Route('/competencia/{ano}/{mes}', name: 'transacao_por_competencia', methods: ['GET'])]
    public function competencia(int $ano, int $mes): array
    {
        $competencia = new \DateTime("$ano-$mes-01");

        return $this->service->buscarPorCompetencia($competencia);
    }
}
