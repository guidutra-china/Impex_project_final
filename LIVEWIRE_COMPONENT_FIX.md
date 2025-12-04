# Correção: Livewire Component Not Found

**Erro:** `Unable to find component: [app.filament.resources.shipments.relation-managers.shipment-containers-relation-manager]`

## Causa

O RelationManager `ShipmentContainersRelationManager` foi atualizado com o método `mount()`, mas o Livewire pode estar usando uma versão em cache do componente.

## Solução

Execute os seguintes comandos para limpar o cache e registrar os componentes corretamente:

### 1. Limpar Cache do Laravel
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 2. Recompilar Autoloader
```bash
composer dump-autoload
```

### 3. Recompilar Assets (se necessário)
```bash
npm run build
# ou
yarn build
```

### 3.5. Limpar Cache de Rota
```bash
php artisan route:clear
```

### 4. Limpar Cache do Navegador
- Abrir DevTools (F12)
- Ir para Application/Storage
- Limpar todos os caches
- Ou fazer Ctrl+Shift+Delete para limpar histórico/cache

### 5. Fazer Hard Refresh
```
Ctrl+Shift+R (Windows/Linux)
Cmd+Shift+R (Mac)
```

## Verificação

Após executar os comandos acima, verifique se:

1. ✅ O arquivo `app/Filament/Resources/Shipments/RelationManagers/ShipmentContainersRelationManager.php` contém o método `mount()`
2. ✅ O método está registrado em `ShipmentResource::getRelations()`
3. ✅ Não há erros no log: `tail -f storage/logs/laravel.log`

## Se o Erro Persistir

Se o erro continuar após limpar o cache, pode ser um problema mais profundo:

### Verificar Namespace
```bash
grep -n "namespace\|class ShipmentContainersRelationManager" app/Filament/Resources/Shipments/RelationManagers/ShipmentContainersRelationManager.php
```

Deve retornar:
```
3:namespace App\Filament\Resources\Shipments\RelationManagers;
22:class ShipmentContainersRelationManager extends RelationManager
```

### Verificar Registro em ShipmentResource
```bash
grep -A 10 "getRelations" app/Filament/Resources/Shipments/ShipmentResource.php
```

Deve incluir:
```php
ShipmentContainersRelationManager::class,
```

### Verificar Sintaxe PHP
```bash
php -l app/Filament/Resources/Shipments/RelationManagers/ShipmentContainersRelationManager.php
```

Deve retornar: `No syntax errors detected`

## Commits Relacionados

- `812bef6` - Adicionado método `mount()` ao `ShipmentContainersRelationManager`

## Notas Importantes

1. **Em Produção:** Após fazer deploy, execute `php artisan cache:clear` no servidor
2. **Docker:** Se usar Docker, rebuild a imagem após as mudanças
3. **Queue Workers:** Reinicie os workers se houver algum: `php artisan queue:restart`

## Referências

- [Livewire Documentation](https://livewire.laravel.com/)
- [Filament Documentation](https://filamentphp.com/)
