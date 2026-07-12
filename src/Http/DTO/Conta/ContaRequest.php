<?php

namespace App\Http\DTO\Conta;

use App\Domain\Conta\Enum\TipoConta;
use App\Shared\Interface\RequestDTOInterface;
use Symfony\Component\Validator\Constraints as Assert;


class ContaRequest implements RequestDTOInterface
{
    #[Assert\Choice(choices: [TipoConta::class, 'values'])]
    public TipoConta $tipo;

    #[Assert\NotBlank]
    public float $saldoInicial;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 80)]
    public string $nome;

    /**
     * @param string $tipo
     * @param float $saldoInicial
     * @param string $nome
     */
    public function __construct(TipoConta $tipo, float $saldoInicial, string $nome)
    {
        $this->tipo = $tipo;
        $this->saldoInicial = $saldoInicial;
        $this->nome = $nome;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            TipoConta::from($data['tipo']),
            $data['saldoInicial'] ?? 0,
            $data['nome']
        );
    }
}
