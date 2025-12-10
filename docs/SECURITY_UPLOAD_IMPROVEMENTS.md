# Melhorias de Segurança no Upload de Arquivos

**Data:** 10 de Dezembro de 2025
**Autor:** Manus AI

## 1. Resumo

Este documento detalha as melhorias de segurança implementadas no sistema de upload de arquivos do projeto Impex. A análise inicial identificou que, embora existisse um `FileUploadService` com boas práticas de segurança, sua aplicação não era consistente em todo o sistema, criando potenciais vulnerabilidades. As melhorias visam centralizar, padronizar e fortalecer a validação de todos os arquivos enviados para o sistema.

## 2. Análise da Vulnerabilidade

A principal vulnerabilidade identificada foi o uso do `saveUploadedFileUsing` no formulário de configurações da empresa (`CompanySettingsForm`) para salvar o logotipo diretamente no disco público. Este método, embora funcional, não passava pelo `FileUploadService`, o que significava que as validações de segurança (MIME type, extensões perigosas, etc.) não eram aplicadas a este upload específico. Isso abria uma brecha para que um usuário mal-intencionado pudesse, teoricamente, enviar um arquivo perigoso disfarçado de imagem.

## 3. Melhorias Implementadas

Para mitigar esta e outras potenciais vulnerabilidades, foram implementadas três camadas de melhorias:

### 3.1. Refatoração do Ponto de Vulnerabilidade

O `FileUpload` do logotipo em `CompanySettingsForm` foi a primeira área a ser corrigida. O código foi refatorado para utilizar o `FileUploadService`, garantindo que o arquivo passe por todas as validações de segurança antes de ser processado. O fluxo agora é:

1.  O arquivo é enviado para o `FileUploadService`.
2.  O serviço valida o arquivo (MIME, extensão, tamanho) e o armazena de forma segura em um disco **privado**.
3.  Se a validação for bem-sucedida, o arquivo é movido do disco privado para o disco **público** para que possa ser exibido na interface.
4.  O arquivo temporário no disco privado é excluído.

### 3.2. Criação do Trait `SecureFileUpload`

Para evitar a repetição de código e garantir que todos os futuros uploads no Filament sigam o mesmo padrão seguro, foi criado o trait `App\Filament\Traits\SecureFileUpload`. Este trait encapsula a lógica de upload seguro e fornece métodos padronizados para serem usados nos formulários do Filament.

**Principais Métodos:**

-   `secureUploadPrivate(string $category, string $storagePath)`: Retorna uma closure para o `saveUploadedFileUsing` que valida e salva o arquivo em um disco **privado**.
-   `secureUploadPublic(string $category, string $publicPath)`: Retorna uma closure que valida o arquivo, o salva temporariamente no disco privado, e depois o move para o disco **público**.
-   `getAcceptedFileTypes(string $category)`: Retorna os tipos de MIME permitidos para uma categoria.
-   `getMaxFileSize(string $category)`: Retorna o tamanho máximo do arquivo para uma categoria.

O `CompanySettingsForm` foi refatorado novamente para utilizar este trait, simplificando o código e garantindo a aplicação do padrão de segurança.

### 3.3. Middleware de Validação Global (`ValidateFileUploads`)

Para adicionar uma camada extra de defesa, foi criado o middleware `App\Http\Middleware\ValidateFileUploads`. Este middleware intercepta **todas** as requisições que contêm uploads de arquivos e aplica um conjunto de validações de segurança de baixo nível, antes mesmo que o arquivo chegue à lógica da aplicação (como o `FileUploadService` ou os componentes do Filament).

**Validações Realizadas pelo Middleware:**

-   **Tamanho Máximo Global:** Impõe um limite global de 50MB.
-   **Extensões Perigosas:** Bloqueia uma lista de extensões de arquivo conhecidas por serem perigosas (ex: `.php`, `.exe`, `.sh`).
-   **MIME Types Suspeitos:** Bloqueia MIME types que são frequentemente associados a arquivos maliciosos (ex: `application/x-php`).
-   **Path Traversal:** Verifica se o nome do arquivo contém sequências como `../` que poderiam ser usadas para tentar acessar outros diretórios no servidor.
-   **Null Bytes:** Verifica a presença de `null bytes` no nome do arquivo, uma técnica de ataque conhecida.

Este middleware ainda não foi ativado globalmente. Ele precisa ser registrado no `app/Http/Kernel.php` para ser aplicado às rotas relevantes. Esta será uma próxima etapa.

## 4. Conclusão

Com estas melhorias, o sistema de upload de arquivos está significativamente mais seguro e robusto. A combinação de um serviço de validação centralizado (`FileUploadService`), um padrão de implementação padronizado para a UI (`SecureFileUpload` trait) e uma camada de defesa global (`ValidateFileUploads` middleware) cria uma abordagem de segurança em profundidade, minimizando o risco de uploads maliciosos.
