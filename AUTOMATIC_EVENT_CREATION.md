# Design do Sistema de Criação Automática de Eventos

Este documento descreve a arquitetura e a implementação do sistema de criação automática de eventos para processos de negócio importantes.

## 1. Visão Geral

O objetivo é criar eventos no calendário automaticamente com base em ações específicas que ocorrem no sistema, como a criação de uma fatura de venda ou a atualização de uma remessa. Isso ajuda a garantir que os usuários sejam notificados sobre prazos e datas importantes sem a necessidade de criação manual de eventos.

A implementação utilizará **Observers** do Eloquent para ouvir os eventos dos models e acionar a criação de eventos.

## 2. Processos de Negócio e Gatilhos de Eventos

A tabela a seguir detalha quais processos de negócio irão gerar eventos automáticos:

| Processo de Negócio | Model | Gatilho (Eloquent Event) | Tipo de Evento Gerado | Data do Evento | Título do Evento |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Vencimento de Fatura | `SalesInvoice` | `created` | `payment` | `due_date` da fatura | `Vencimento da Fatura #{invoice_number}` |
| Chegada de Remessa | `Shipment` | `created` ou `updated` | `shipment` | `estimated_arrival_date` | `Chegada da Remessa #{tracking_number}` |
| Prazo de Entrega de PO | `PurchaseOrder` | `created` | `deadline` | `delivery_date` | `Entrega do Pedido de Compra #{po_number}` |
| Lembrete de Follow-up de RFQ | `Order` | `updated` (status para `sent`) | `reminder` | 3 dias após o envio | `Follow-up da RFQ #{order_number}` |

## 3. Implementação com Observers

Para cada model que dispara um evento, um Observer será criado. Esses observers conterão a lógica para criar o `Event` correspondente.

### 3.1. Estrutura do Observer

Cada observer terá um método para o gatilho correspondente (por exemplo, `created`). Dentro deste método, um novo `Event` será instanciado e salvo com os seguintes atributos:

-   `user_id`: O ID do usuário responsável (por exemplo, o proprietário do cliente associado).
-   `title`: Título dinâmico baseado no model de origem.
-   `start`: A data relevante (vencimento, chegada, etc.).
-   `event_type`: O tipo de evento correspondente da tabela acima.
-   `related_type`: A classe do model de origem (ex: `SalesInvoice::class`).
-   `related_id`: O ID do model de origem.
-   `is_automatic`: Sempre `true`.
-   `color`: A cor padrão para o tipo de evento.

### 3.2. Exemplo: `SalesInvoiceObserver`

```php
namespace App\Observers;

use App\Models\SalesInvoice;
use App\Models\Event;

class SalesInvoiceObserver
{
    public function created(SalesInvoice $invoice): void
    {
        if ($invoice->due_date && $invoice->client->user_id) {
            Event::create([
                'user_id' => $invoice->client->user_id,
                'title' => "Vencimento da Fatura #{$invoice->invoice_number}",
                'start' => $invoice->due_date,
                'event_type' => Event::TYPE_PAYMENT,
                'related_type' => SalesInvoice::class,
                'related_id' => $invoice->id,
                'is_automatic' => true,
                'color' => Event::getEventColors()[Event::TYPE_PAYMENT],
            ]);
        }
    }
}
```

## 4. Registro dos Observers

Os observers serão registrados no `ObserverServiceProvider.php` para que o Laravel possa começar a ouvi-los.

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SalesInvoice;
use App\Observers\SalesInvoiceObserver;
// ... outros imports

class ObserverServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        SalesInvoice::observe(SalesInvoiceObserver::class);
        // ... outros observers
    }
}
```

Este design garante um sistema de criação de eventos desacoplado, extensível e fácil de manter.
