# Guia de Configuração de Eventos e Calendário

Este documento fornece as instruções para configurar a nova funcionalidade de gerenciamento de eventos e o widget de calendário no painel de administração.

## 1. Executar a Migração do Banco de Dados

A tabela `events` precisa ser criada no seu banco de dados. Para fazer isso, execute o seguinte comando no terminal, na raiz do seu projeto Laravel:

```bash
php artisan migrate
```

Isso criará a tabela `events` com todos os campos necessários para armazenar as informações dos eventos.

## 2. Gerar Permissões com Filament Shield

Para garantir que apenas usuários autorizados possam gerenciar os eventos e visualizar o calendário, você precisa gerar as permissões usando o Filament Shield.

### 2.1. Gerar Permissões para o Recurso de Eventos

Execute o seguinte comando para gerar automaticamente as permissões para o `EventResource`:

```bash
php artisan shield:generate --resource=EventResource
```

Este comando criará as seguintes permissões:

- `view_any_event`
- `view_event`
- `create_event`
- `update_event`
- `delete_event`
- `restore_event`
- `force_delete_event`

### 2.2. Criar Permissão para o Widget do Calendário

A permissão para o `CalendarWidget` precisa ser criada manualmente. O widget espera a permissão `View:CalendarWidget`. Você pode criar esta permissão no painel do Filament Shield ou via seeder.

Para criar via seeder, adicione o seguinte ao seu `ShieldSeeder.php` ou a um seeder de sua escolha:

```php
use Spatie\Permission\Models\Permission;

Permission::create(["name" => "View:CalendarWidget"]);
```

Depois de adicionar, execute o seeder:

```bash
php artisan db:seed --class=ShieldSeeder
```

### 2.3. Atribuir Permissões às Funções (Roles)

Após criar as permissões, você precisa atribuí-las às funções (roles) desejadas através do painel de administração do Filament Shield.

1.  Vá para a seção **Shield** no seu painel de administração.
2.  Selecione a função (role) que deseja editar (por exemplo, `super_admin`, `manager`).
3.  Marque as caixas de seleção para as permissões de `event` e `View:CalendarWidget` que você deseja conceder a essa função.
4.  Salve as alterações.

## 3. Testar a Funcionalidade

Após concluir os passos acima, a funcionalidade de eventos e o calendário devem estar prontos para uso.

1.  **Verifique o Dashboard:** O widget do calendário deve aparecer no dashboard para os usuários com a permissão `View:CalendarWidget`.
2.  **Acesse o Recurso de Eventos:** O item de menu "Calendário" deve aparecer no grupo de navegação "Settings" para usuários com as permissões apropriadas.
3.  **Crie um Evento:** Tente criar um novo evento através do recurso de eventos para garantir que o formulário está funcionando corretamente.
4.  **Visualize no Calendário:** O evento recém-criado deve aparecer no widget do calendário no dashboard.

Se todos os passos foram seguidos corretamente, o sistema de gerenciamento de eventos estará totalmente funcional.
