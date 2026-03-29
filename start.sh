#!/bin/bash

# Roda a importacao apenas se a tabela operacoes estiver vazia
COUNT=$(php artisan tinker --execute="echo \App\Models\Operacao::count();" 2>/dev/null | tail -1)

if [ "$COUNT" = "0" ]; then
    echo "Importando operacoes..."
    php artisan operacoes:importar
fi

# Inicia o servidor
php artisan serve --host=0.0.0.0 --port=8080