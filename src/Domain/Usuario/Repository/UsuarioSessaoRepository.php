<?php

namespace App\Domain\Usuario\Repository;

use App\Domain\Usuario\Entity\Usuario;
use App\Domain\Usuario\Entity\UsuarioSessao;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UsuarioSessaoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UsuarioSessao::class);
    }

    /**
     * Criar ou atualizar entidade no banco.
     */
    public function save(UsuarioSessao $sessao, bool $flush = true): void
    {
        $this->getEntityManager()->persist($sessao);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retorna uma sessão válida pelo token.
     */
    public function findByToken(string $token): ?UsuarioSessao
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.token = :token')
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Remove a sessão do banco.
     */
    public function remove(UsuarioSessao $sessao, bool $flush = true): void
    {
        $this->getEntityManager()->remove($sessao);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Busca a sessão pelo refresh token.
     * Retorna null se revogada ou expirada.
     */
    public function buscarPorRefreshToken(string $token): ?UsuarioSessao
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.refreshToken = :token')
            ->andWhere('s.revogado = false')
            ->setParameter('token', $token);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Busca uma sessão pelo seu ID.
     *
     * @return UsuarioSessao|null
     */
    public function buscarPorId(int $id): ?UsuarioSessao
    {
        return $this->createQueryBuilder('s')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca todas as sessões ativas do usuário.
     */
    public function buscarAtivasPorUsuario(Usuario $usuario): array
    {
        $params = [
            'usuario' => $usuario,
            'agora' => new \DateTimeImmutable(),
        ];

        return $this->createQueryBuilder('s')
            ->where('s.usuario = :usuario')
            ->andWhere('s.revogado = false')
            ->andWhere('s.dataExpiracao > :agora')
            ->setParameters($params)
            ->orderBy('s.criadoEm', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifica se o usuário já tem uma sessão ativa em um dispositivo específico.
     * Pode ser útil para limitar sessõess por device.
     */
    public function buscarPorUsuarioEUserAgent(Usuario $usuario, string $userAgent): ?UsuarioSessao
    {
        $params = [
            'usuario' => $usuario,
            'ua' => $userAgent,
        ];

        return $this->createQueryBuilder('s')
            ->where('s.usuario = :usuario')
            ->andWhere('s.userAgent = :ua')
            ->andWhere('s.revogado = false')
            ->setParameters($params)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Revoga todas as sessões do usuário — útil em:
     * - Reset de senha
     * - Logout global
     * - Conta comprometida
     */
    public function revogarTodasPorUsuario(Usuario $usuario): int
    {
        $params = [
            'rev' => true,
            'usuario' => $usuario,
        ];

        return $this->createQueryBuilder('s')
            ->update()
            ->set('s.revogado', ':rev')
            ->where('s.usuario = :usuario')
            ->setParameters($params)
            ->getQuery()
            ->execute();
    }

    /**
     * Remove sessões expiradas (cron job ou auto cleanup).
     */
    public function limparExpiradas(): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.dataExpiracao < :agora')
            ->setParameter('agora', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Atualiza a data de último uso de uma sessão.
     * Importante para auditoria, antifraude e refresh seguro.
     */
    public function atualizarUltimoUso(UsuarioSessao $sessao): void
    {
        $sessao->setUltimoAcesso(new \DateTimeImmutable());
        $this->save($sessao);
    }
}
