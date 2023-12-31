<?php

namespace App\Actions\Transacao;

use App\Exceptions\CaixaFechadoException;
use App\Exceptions\EstoqueProdutoInsuficienteException;
use App\Exceptions\StatusTransacaoInvalidoParaAdicionarOuRemoverProdutosException;
use App\Models\Produto;
use App\Models\Statuses\Transacao\Iniciada;
use App\Models\Transacao;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class AdicionarProdutoTransacao
{
    use AsAction;

    public function handle(string $transacaoId, string $produtoId, string $quantidade): void
    {
        $transacao = Transacao::query()->findOrFail($transacaoId);
        $produto = Produto::query()->findOrFail($produtoId);

        $this->verificaEstoqueProduto($produto, $quantidade);

        DB::transaction(function () use ($transacao, $produto, $quantidade) {

            $produto->update([
                'qtd_estoque' => $this->calculaQtdEstoqueProduto($produto, $transacao, $quantidade)
            ]);

            $transacao->produtos()->syncWithoutDetaching([
                $produto->id => [
                    'quantidade' => $quantidade
                ]
            ]);
        });
    }

    public function asController(Transacao $transacao, Produto $produto, ActionRequest $request): Response
    {
        // TODO: Trocar essa linha para buscar o id do usuário autenticado
        $userId = User::first()->id;

        $this->verificaPermissaoUsuario($userId, $transacao);
        $this->verificaCaixaTransacaoAberto($transacao);
        $this->verificaStatusTransacao($transacao);

        $this->handle($transacao->id, $produto->id, $request->input('quantidade'));

        return response()->noContent();
    }

    public function rules(): array
    {
        return [
            'quantidade' => ['required', 'string', 'numeric']
        ];
    }

    /** Métodos auxiliares */

    protected function verificaPermissaoUsuario(string $userId, Transacao $transacao): void
    {
        if ($userId !== $transacao->caixa->user_id) {
            throw new ModelNotFoundException;
        }
    }

    protected function verificaCaixaTransacaoAberto(Transacao $transacao): void
    {
        if ($transacao->caixa->data_hora_fechamento) {
            throw new CaixaFechadoException;
        }
    }

    protected function verificaStatusTransacao(Transacao $transacao): void
    {
        if (!$transacao->status->equals(Iniciada::class)) {
            throw new StatusTransacaoInvalidoParaAdicionarOuRemoverProdutosException;
        }
    }

    protected function verificaEstoqueProduto(Produto $produto, string $quantidade): void
    {
        if ($quantidade > $produto->qtd_estoque) {
            throw new EstoqueProdutoInsuficienteException;
        }
    }

    protected function calculaQtdEstoqueProduto(Produto $produto, Transacao $transacao, string $quantidade): string
    {
        $produtoNaTransacao = $transacao->produtos()->find($produto->id);

        $qtdEstoque = $produto->qtd_estoque;

        if ($produtoNaTransacao) {
            $qtdEstoque = bcadd($produto->qtd_estoque, $produtoNaTransacao->pivot->quantidade, $produto->unidadeMedida->decimais);
        }

        return bcsub($qtdEstoque, $quantidade, 2);
    }
}
