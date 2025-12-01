# 🧪 Teste de Widgets - Instruções

## Execute Este Teste

```bash
cd /caminho/para/seu/projeto
php test_widgets.php
```

## O Que Vai Mostrar

O script vai verificar:

1. ✅ Se as classes dos widgets existem
2. ✅ Se os arquivos dos widgets existem
3. ✅ Configuração do Shield
4. ✅ Widgets registrados no painel
5. ✅ Permissões no banco de dados
6. ✅ Tabs do Shield habilitadas

## Depois de Executar

**Copie TODO o output** e me envie para eu analisar.

---

## Comandos Alternativos

Se o teste acima não funcionar, tente:

### **1. Verificar Permissões no Banco**

```bash
php artisan tinker
```

```php
$permissions = Spatie\Permission\Models\Permission::where('name', 'LIKE', '%widget%')->get();
echo "Total: " . $permissions->count() . "\n";
foreach ($permissions as $perm) {
    echo "- {$perm->name}\n";
}
exit
```

### **2. Gerar Permissões Manualmente**

```bash
php artisan shield:generate --all
```

### **3. Verificar Widgets Registrados**

```bash
php artisan tinker
```

```php
$panel = Filament\Facades\Filament::getPanel('admin');
$widgets = $panel->getWidgets();
echo "Total widgets: " . count($widgets) . "\n";
foreach ($widgets as $w) {
    echo "- " . (is_string($w) ? $w : get_class($w)) . "\n";
}
exit
```

### **4. Limpar Tudo e Recomeçar**

```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan permission:cache-reset
composer dump-autoload
php artisan shield:generate --all
```

---

## Me Envie

Após executar o teste, me envie:

1. ✅ Output completo do `php test_widgets.php`
2. ✅ Screenshot da aba "Widgets" no Shield (se aparecer)
3. ✅ Qualquer erro que aparecer

Com essas informações vou identificar exatamente o problema! 🔍
