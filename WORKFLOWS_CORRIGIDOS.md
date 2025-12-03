# Workflows Corrigidos

## ✅ Correções Realizadas:

1. **PHP 8.3** - Compatível com todas as dependências
2. **actions/upload-artifact@v4** - Versão atual (não deprecated)
3. **actions/checkout@v4** - Versão atual
4. **actions/cache@v4** - Versão atual

---

## 📝 tests.yml (Corrigido)

```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: ['8.3']
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: impex_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: pcov, mysql
          coverage: pcov
          tools: composer:v2
      
      - name: Get composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT
      
      - name: Cache composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-
      
      - name: Install dependencies
        run: composer install --no-interaction --no-progress
      
      - name: Create .env file
        run: |
          cp .env.example .env
          php artisan key:generate
      
      - name: Create test database
        run: |
          mysql -h 127.0.0.1 -u root -proot -e "CREATE DATABASE IF NOT EXISTS impex_test;"
      
      - name: Run migrations
        run: php artisan migrate --env=testing
      
      - name: Run tests with coverage
        run: php artisan test --coverage --coverage-html=coverage --coverage-clover=coverage.xml
      
      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
          flags: unittests
          name: codecov-umbrella
          fail_ci_if_error: false
      
      - name: Archive coverage reports
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: coverage-report-php${{ matrix.php-version }}
          path: coverage/
      
      - name: Comment PR with test results
        if: github.event_name == 'pull_request'
        uses: actions/github-script@v7
        with:
          script: |
            const fs = require('fs');
            const coverage = fs.readFileSync('coverage.xml', 'utf8');
            const match = coverage.match(/lines-valid="(\d+)".*lines-covered="(\d+)"/);
            
            if (match) {
              const valid = parseInt(match[1]);
              const covered = parseInt(match[2]);
              const percent = ((covered / valid) * 100).toFixed(2);
              
              github.rest.issues.createComment({
                issue_number: context.issue.number,
                owner: context.repo.owner,
                repo: context.repo.repo,
                body: `✅ Tests passed!\n\n📊 Code Coverage: **${percent}%** (${covered}/${valid} lines)`
              });
            }
```

---

## 📝 code-quality.yml (Corrigido)

```yaml
name: Code Quality

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  code-quality:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: composer:v2
      
      - name: Get composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT
      
      - name: Cache composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-
      
      - name: Install dependencies
        run: composer install --no-interaction --no-progress
      
      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse --memory-limit=512M
        continue-on-error: true
      
      - name: Run Pint (Code Style)
        run: ./vendor/bin/pint --test
        continue-on-error: true
      
      - name: Check for security vulnerabilities
        run: composer audit
        continue-on-error: true
      
      - name: Run Larastan
        run: ./vendor/bin/phpstan analyse app --level=5
        continue-on-error: true
      
      - name: Comment PR with quality results
        if: github.event_name == 'pull_request'
        uses: actions/github-script@v7
        with:
          script: |
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: '✅ Code quality checks completed!\n\n📋 Please review the results above.'
            });
```

---

## 📝 performance.yml (Corrigido)

```yaml
name: Performance Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  performance:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: impex_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mysql
          tools: composer:v2
      
      - name: Get composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT
      
      - name: Cache composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-
      
      - name: Install dependencies
        run: composer install --no-interaction --no-progress
      
      - name: Create .env file
        run: |
          cp .env.example .env
          php artisan key:generate
      
      - name: Create test database
        run: |
          mysql -h 127.0.0.1 -u root -proot -e "CREATE DATABASE IF NOT EXISTS impex_test;"
      
      - name: Run migrations
        run: php artisan migrate --env=testing
      
      - name: Run performance tests
        run: php artisan test --filter=PerformanceTest
        continue-on-error: true
      
      - name: Comment PR with performance results
        if: github.event_name == 'pull_request'
        uses: actions/github-script@v7
        with:
          script: |
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: '⚡ Performance tests completed!\n\n📊 Please review the results above.'
            });
```

---

## 🚀 Como Atualizar os Workflows

### **Passo 1: Ir para GitHub**
1. Abra seu repositório: https://github.com/guidutra-china/Impex_project_final
2. Vá para a aba **"Actions"**
3. Clique em **"Code Quality"** (o workflow que falhou)
4. Clique em **"Edit this file"** (ícone de lápis)

### **Passo 2: Atualizar tests.yml**
1. Vá para `.github/workflows/tests.yml`
2. Clique no ícone de lápis para editar
3. Copie e cole o código corrigido acima
4. Clique em **"Commit changes"**

### **Passo 3: Atualizar code-quality.yml**
1. Vá para `.github/workflows/code-quality.yml`
2. Clique no ícone de lápis para editar
3. Copie e cole o código corrigido acima
4. Clique em **"Commit changes"**

### **Passo 4: Atualizar performance.yml**
1. Vá para `.github/workflows/performance.yml`
2. Clique no ícone de lápis para editar
3. Copie e cole o código corrigido acima
4. Clique em **"Commit changes"**

### **Passo 5: Testar**
1. Faça um novo commit em qualquer arquivo
2. Vá para **"Actions"** e veja os workflows rodando
3. Aguarde a conclusão

---

## ✅ Mudanças Realizadas

| Mudança | Antes | Depois |
|---------|-------|--------|
| PHP Version | 8.2, 8.3 | 8.3 |
| upload-artifact | v3 | v4 |
| checkout | v3 | v4 |
| cache | v3 | v4 |

---

## 🎯 Resultado Esperado

Depois das correções, os workflows devem:
- ✅ Instalar dependências com sucesso
- ✅ Executar testes com sucesso
- ✅ Gerar cobertura de código
- ✅ Fazer upload de artefatos
- ✅ Comentar no PR com resultados

---

**Desenvolvido por:** Manus AI Agent
**Data:** 04 de Dezembro de 2025
