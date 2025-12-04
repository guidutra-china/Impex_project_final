# Troubleshooting: Livewire Component Not Found

**Error:** `Unable to find component: [app.filament.resources.shipments.relation-managers.shipment-containers-relation-manager]`

**Status:** 🔴 Persistente - Requer investigação adicional

## Análise do Problema

### Sintomas
- Erro ocorre ao tentar acessar a página de edição de Shipment
- URL: `/panel/shipments/{id}/edit?relation=3`
- Erro é lançado em `POST /livewire/update`
- Ocorre mesmo após limpeza de cache

### Verificações Realizadas ✅

1. **Arquivo existe:** ✅ `app/Filament/Resources/Shipments/RelationManagers/ShipmentContainersRelationManager.php`
2. **Namespace correto:** ✅ `App\Filament\Resources\Shipments\RelationManagers`
3. **Classe herda corretamente:** ✅ `extends RelationManager`
4. **Método mount() existe:** ✅ Adicionado
5. **Atributo $relationship:** ✅ `'containers'`
6. **Relação no Model:** ✅ `Shipment::containers()` existe e retorna `HasMany`
7. **Registrado em ShipmentResource:** ✅ Incluído em `getRelations()`
8. **Sintaxe PHP:** ✅ Sem erros

### Possíveis Causas

#### 1. **Cache do Livewire**
O Livewire pode estar usando uma versão em cache do componente que não inclui o RelationManager.

**Solução:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan livewire:discover
```

#### 2. **Problema de Namespace Dinâmico**
O Livewire pode estar procurando o componente em um namespace diferente do esperado.

**Verificar:**
```bash
php artisan tinker
> Livewire::getRegisteredComponents()
```

#### 3. **Problema de Autoload do Composer**
O arquivo pode não estar sendo carregado corretamente pelo autoloader do Composer.

**Solução:**
```bash
composer dump-autoload
php artisan cache:clear
```

#### 4. **Problema com Filament 4**
Filament 4 pode ter mudanças na forma como registra RelationManagers que não são compatíveis com a implementação atual.

**Verificar versão:**
```bash
composer show filament/filament
```

## Commits Relacionados

| Commit | Descrição |
|--------|-----------|
| `21cb4f1` | Adicionado atributo `$title` ao RelationManager |
| `812bef6` | Adicionado método `mount()` |
| `61a6c7a` | Instruções de cache clearing |

## Próximos Passos

### 1. Verificar Logs
```bash
tail -f storage/logs/laravel.log
```

### 2. Verificar Componentes Registrados
```bash
php artisan tinker
> \Livewire\Livewire::getRegisteredComponents()->keys()
```

### 3. Forçar Recompilação
```bash
composer dump-autoload
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Reiniciar Servidor
```bash
# Se usar Herd/Valet
valet restart

# Se use artisan serve
# Ctrl+C e reiniciar
php artisan serve
```

## Solução Alternativa (Se Necessário)

Se o problema persistir, pode ser necessário:

1. **Renomear o arquivo** para seguir um padrão diferente
2. **Registrar manualmente** o componente em um Service Provider
3. **Usar um padrão diferente** para o RelationManager

## Informações do Sistema

- **PHP:** 8.4.15
- **Laravel:** 12.39.0
- **Livewire:** (verificar com `composer show livewire/livewire`)
- **Filament:** 4.x

## Referências

- [Livewire Component Discovery](https://livewire.laravel.com/docs/components#component-discovery)
- [Filament RelationManager Documentation](https://filamentphp.com/docs/3.x/panels/resources/relation-managers)
- [Laravel Autoloading](https://laravel.com/docs/12.x/autoloading)

## Notas para Desenvolvedor

Este erro é específico do Livewire e sua capacidade de descobrir e registrar componentes dinamicamente. O problema pode estar em:

1. **Timing:** O componente pode estar sendo carregado antes de estar totalmente registrado
2. **Cache:** Múltiplas camadas de cache podem estar interferindo
3. **Namespace:** O Livewire pode estar procurando em um namespace diferente

Recomenda-se:
- Limpar **todos** os caches (Laravel, Livewire, navegador)
- Reiniciar o servidor web
- Verificar os logs para mensagens de erro adicionais
- Considerar usar `php artisan tinker` para debug interativo
