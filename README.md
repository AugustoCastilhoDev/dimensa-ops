# Dimensa Ops — Sistema de Gestão de Operações Financeiras


Sistema desenvolvido para o processo seletivo da Dimensa Tecnologia.
Permite importar, visualizar e gerenciar operações financeiras a partir
de uma planilha com alto volume de dados (50 mil registros).


## Como executar o projeto


### Pré-requisitos
- Laragon Full (PHP 8.3, MySQL 8.0) — laragon.net
- Composer — getcomposer.org
- Node.js 20+ — nodejs.org
- Git — git-scm.com


### Passos
```bash
git clone <url-do-repositorio>
cd dimensa-app
cp .env.example .env
```


Edite o `.env` com suas credenciais de banco:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dimensa
DB_USERNAME=root
DB_PASSWORD=

text


Crie o banco no MySQL antes de continuar:
```sql
CREATE DATABASE dimensa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```


Continue no terminal:
```bash
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```


Acesse: [http://dimensa-app.test](http://dimensa-app.test)
Login: [admin@dimensa.com](mailto:admin@dimensa.com) | Senha: password


---


## Como executar a importação


Coloque o arquivo Excel fornecido em:
storage/app/imports/operacoes.xlsx

text


Execute o comando:
```bash
php artisan operacoes:importar
```


A importação processa os 50.000 registros em chunks de 500 linhas com
barra de progresso no terminal. Tempo estimado: 3 a 8 minutos.


---


## Decisões técnicas


- **Importação em chunks transacionais**: 500 linhas por transação para
  evitar timeout e garantir rollback em caso de erro parcial. Utiliza
  leitura linha a linha via iterador do PhpSpreadsheet para evitar
  estouro de memória ao carregar o arquivo completo.


- **Parcela::insert() em lote**: uma única query por operação ao invés
  de N inserts individuais — reduz o tempo de importação em ~60%.


- **Eager loading obrigatório**: todas as queries usam with() para
  evitar o problema N+1 na listagem paginada com 50 registros por página.


- **protected $table nos Models**: como os nomes das entidades são em
  português (Operação, Parcela, Conveniada), o Laravel pluralizaria
  incorretamente para operacaos, parcelas etc. A propriedade $table
  garante o nome correto da tabela.


- **Status e transições como constantes no Model**: fonte única de
  verdade para labels e regras de transição, sem hardcode em
  controllers ou views.


- **Service ValorPresenteService**: lógica financeira isolada em
  service dedicado, facilitando testes unitários e reutilização na
  listagem e na exportação.


- **Exportação via stream (response()->stream)**: para suportar o
  volume total de 50k registros sem timeout, a exportação usa streaming
  direto para CSV com chunk de 500 registros, evitando carregar tudo
  na memória simultaneamente.


- **Configuração do PATH via VSCode settings.json**: para garantir que
  o PHP do Laragon seja sempre utilizado no terminal integrado,
  independente de outros PHPs instalados no sistema.


---


## Limitações


- CPF não validado (armazenado como varchar conforme especificado).
- Sem autorização por perfil (todos os usuários autenticados têm acesso total).
- Importação sem interface web de progresso (acompanhamento apenas pelo terminal).
- Valor Presente recalculado a cada exportação (sem cache).
- Exportação disponível apenas em CSV (Excel trava com 50k registros sem queue).
- Dados de teste importados com status_id fixo — necessário atualizar
  manualmente via SQL para testar outros status.


---


## O que melhoraria com mais tempo


- Dashboard com gráficos por conveniada, produto e status (Chart.js).
- Testes automatizados (Feature + Unit) para regras de status e cálculo de VP.
- Autorização por perfil: analista, gerente, admin com permissões distintas.
- Cache Redis para queries de listagem frequentes.
- Progresso em tempo real da importação via Livewire ou Server-Sent Events.
- Exportação Excel em background via Queue com notificação ao usuário.
- Validação e formatação de CPF (máscara e dígitos verificadores).
- Paginação com opção de escolher quantos registros exibir por página.