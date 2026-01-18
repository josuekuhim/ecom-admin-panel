# Notas de Segurança & Clean Architecture

## Clerk
- A autenticação da API agora exige o bearer token do Clerk em `ClerkAuthMiddleware`; o header `X-Clerk-User-Id` sozinho é rejeitado.
- Webhooks agora usam verificação Svix com `CLERK_WEBHOOK_SECRET` (obrigatório). Requisições sem assinatura válida retornam 401.
- O `CustomerService` centraliza criação/atualização a partir do Clerk, garantindo criação de carrinho e registro de login.

## InfinitePay
- Webhooks exigem `INFINITEPAY_WEBHOOK_SECRET`; assinaturas inválidas ou ausentes retornam 401/500.
- Pedidos já finalizados (`paid/failed/canceled`) não são sobrescritos por eventos posteriores.

## Pedidos & Estoque
- A criação de pedido faz lock dos variants e valida estoque antes de decrementar (no momento do pedido, não no carrinho).
- Endpoints de carrinho não alteram mais o estoque; apenas validam disponibilidade.

## Validação & API
- FormRequests adicionados para autenticação, carrinho e pedidos, garantindo validação consistente dos inputs.
- Respostas da API de carrinho/pedido agora usam Resources para padronizar a saída e evitar vazamento de campos internos.

## Policies
- `OrderPolicy` restringe visualização ao dono; `CartPolicy`/`CartItemPolicy` restringem operações ao dono do carrinho/item. `AuthServiceProvider` registrado em `bootstrap/providers.php`.

## Boas práticas de logs
- Evite logar payloads completos com dados sensíveis (PII); mantenha logs operacionais mínimos (próximo passo: revisar logs restantes).

## Variáveis de ambiente obrigatórias
- `CLERK_WEBHOOK_SECRET` (obrigatório para webhooks do Clerk)
- `INFINITEPAY_WEBHOOK_SECRET` (obrigatório para webhooks do InfinitePay)

## Próximos passos
- Sanitizar/reduzir logs restantes; mascarar e-mails/telefones.
- Considere implementar reserva/expiração de carrinho caso queira segurar estoque antes do checkout.
- Se implementar reserva, documentar SLA/TTL e fluxo de liberação (job/cron ou evento em pagamento falhado).
