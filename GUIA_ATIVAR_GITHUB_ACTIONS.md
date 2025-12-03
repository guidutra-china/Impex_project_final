# Guia Passo a Passo: Ativar GitHub Actions

**Objetivo:** Ativar os workflows de CI/CD no seu repositório GitHub

**Tempo Estimado:** 10-15 minutos

---

## 📋 Pré-requisitos

- ✅ Acesso ao repositório GitHub (como Admin ou Owner)
- ✅ Permissão para modificar Settings
- ✅ Permissão para criar/editar workflows

---

## 🚀 Método 1: Usar GitHub Web Interface (Recomendado)

### **Passo 1: Acessar o Repositório**

1. Abra seu navegador
2. Vá para: `https://github.com/guidutra-china/Impex_project_final`
3. Você deve estar logado na sua conta GitHub
4. Se não estiver, clique em "Sign in" e faça login

### **Passo 2: Acessar a Aba Actions**

1. No repositório, clique na aba **"Actions"** (entre "Pull requests" e "Projects")
2. Você verá a página de Actions do repositório
3. Pode aparecer uma mensagem "No workflows found" - isso é normal

### **Passo 3: Verificar Permissões de Workflows**

1. Clique em **"Settings"** (aba no topo do repositório)
2. No menu lateral esquerdo, clique em **"Actions"** (dentro de "Code and automation")
3. Você verá a página de configurações de Actions

### **Passo 4: Habilitar GitHub Actions**

Na página de Actions settings:

1. Procure por **"General"** (deve estar selecionado por padrão)
2. Procure pela seção **"Actions permissions"**
3. Selecione a opção: **"Allow all actions and reusable workflows"**
4. Clique em **"Save"**

### **Passo 5: Permitir Workflows Criar PRs**

Ainda na mesma página:

1. Procure pela seção **"Workflow permissions"**
2. Marque a opção: **"Allow GitHub Actions to create and approve pull requests"**
3. Selecione: **"Read and write permissions"** (se disponível)
4. Clique em **"Save"**

### **Passo 6: Criar os Workflows Manualmente**

Agora você precisa criar os arquivos dos workflows no GitHub web interface:

#### **Criar tests.yml:**

1. Vá para a aba **"Code"** do repositório
2. Clique no botão **"Add file"** > **"Create new file"**
3. No campo de nome, digite: `.github/workflows/tests.yml`
4. Copie e cole o conteúdo abaixo:

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
        php-version: ['8.2', '8.3']
    
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
        uses: actions/cache@v3
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
        uses: actions/upload-artifact@v3
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

5. Clique em **"Commit changes..."**
6. Adicione a mensagem: `ci(workflows): adicionar tests workflow`
7. Selecione **"Commit directly to the main branch"**
8. Clique em **"Commit changes"**

#### **Criar code-quality.yml:**

1. Clique novamente em **"Add file"** > **"Create new file"**
2. No campo de nome, digite: `.github/workflows/code-quality.yml`
3. Copie e cole o conteúdo abaixo:

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
          php-version: '8.2'
          tools: composer:v2
      
      - name: Get composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT
      
      - name: Cache composer dependencies
        uses: actions/cache@v3
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

4. Clique em **"Commit changes..."**
5. Adicione a mensagem: `ci(workflows): adicionar code-quality workflow`
6. Selecione **"Commit directly to the main branch"**
7. Clique em **"Commit changes"**

#### **Criar performance.yml:**

1. Clique novamente em **"Add file"** > **"Create new file"**
2. No campo de nome, digite: `.github/workflows/performance.yml`
3. Copie e cole o conteúdo abaixo:

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
          php-version: '8.2'
          extensions: mysql
          tools: composer:v2
      
      - name: Get composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT
      
      - name: Cache composer dependencies
        uses: actions/cache@v3
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

4. Clique em **"Commit changes..."**
5. Adicione a mensagem: `ci(workflows): adicionar performance workflow`
6. Selecione **"Commit directly to the main branch"**
7. Clique em **"Commit changes"**

### **Passo 7: Verificar se os Workflows Foram Criados**

1. Vá para a aba **"Actions"** do repositório
2. Você deve ver os 3 workflows listados:
   - Tests
   - Code Quality
   - Performance Tests
3. Se aparecerem, os workflows foram criados com sucesso!

### **Passo 8: Testar os Workflows**

1. Vá para a aba **"Code"**
2. Crie um novo arquivo ou edite um existente
3. Faça um commit
4. Vá para **"Actions"** e veja os workflows sendo executados
5. Clique em um workflow para ver os detalhes

---

## 🚀 Método 2: Usar GitHub CLI (Alternativa)

Se você tem GitHub CLI instalado:

```bash
# 1. Fazer login
gh auth login

# 2. Ir para o diretório do projeto
cd /home/ubuntu/Impex_project_final

# 3. Fazer push dos workflows
git push origin main

# 4. Verificar status dos workflows
gh run list
```

---

## ✅ Checklist de Verificação

Depois de completar os passos acima, verifique:

- [ ] GitHub Actions está habilitado nas Settings
- [ ] Permissão "Allow all actions" está selecionada
- [ ] Permissão "Allow GitHub Actions to create and approve pull requests" está marcada
- [ ] 3 workflows foram criados (.github/workflows/)
- [ ] Os workflows aparecem na aba "Actions"
- [ ] Os workflows executam em cada push/PR

---

## 🐛 Solução de Problemas

### **Problema: Workflows não aparecem na aba Actions**

**Solução:**
1. Atualize a página (F5)
2. Verifique se os arquivos estão em `.github/workflows/`
3. Verifique se os nomes dos arquivos estão corretos (.yml)
4. Verifique se o YAML está bem formatado (sem erros de sintaxe)

### **Problema: Workflows não executam automaticamente**

**Solução:**
1. Verifique se "Allow all actions" está selecionado
2. Verifique se o trigger está correto (push, pull_request)
3. Verifique se está na branch main ou develop
4. Faça um novo commit para disparar o workflow

### **Problema: Erro "refusing to allow a GitHub App to create or update workflow"**

**Solução:**
1. Use o GitHub Web Interface para criar os workflows (Método 1)
2. Ou use GitHub CLI com autenticação (Método 2)
3. Ou peça a um admin do repositório para fazer push

### **Problema: Testes falhando**

**Solução:**
1. Verifique se o `.env.example` existe
2. Verifique se as migrações estão corretas
3. Verifique se o MySQL está rodando corretamente
4. Veja os logs do workflow para mais detalhes

---

## 📞 Suporte

Se encontrar problemas:

1. Verifique a aba **"Actions"** para ver os logs
2. Clique no workflow que falhou
3. Clique no job que falhou
4. Veja os logs detalhados
5. Procure por mensagens de erro específicas

---

## 🎉 Sucesso!

Se você completou todos os passos e os workflows estão rodando, parabéns! 🎊

Seu repositório agora tem:
- ✅ Testes automáticos
- ✅ Verificação de qualidade de código
- ✅ Testes de performance
- ✅ Comentários automáticos em PRs
- ✅ Relatórios de cobertura

---

**Desenvolvido por:** Manus AI Agent
**Data:** 04 de Dezembro de 2025
