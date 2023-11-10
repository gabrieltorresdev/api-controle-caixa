<?php

namespace App\Exceptions\Caixa;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CaixaFechadoHttpException extends HttpException
{
    protected const STATUS_CODE = 400;
    protected const MESSAGE = 'O caixa está fechado.';

    public function __construct(\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(self::STATUS_CODE, self::MESSAGE, $previous, $headers);
    }
}
