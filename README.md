![N|Solid](https://upload.wikimedia.org/wikipedia/commons/8/80/Logo_PagSeguro.png)

# PagSeguro - Módulo Magento 2.4

## _API Charge_

## Descrição

Esse módulo é compatível com a versão 2.4 do Magento. Ele utiliza a API Charge do PagSeguro, sendo capaz de fazer transações para pagamentos com boleto, um cartão de crédito ou dois cartões de crédito.

## Documentação Oficial

- Instalando e configurando o módulo Magento 2.4 (passo a passo detalhado, com imagens sobre como instalar e configurar o Módulo)
  - https://dev.pagseguro.uol.com.br/docs/instalando-o-magento-24

- Boas práticas de desenvolvimento:
  - https://dev.pagseguro.uol.com.br/docs/boas-pr%C3%A1ticas-de-desenvolvimento

## Instalação utilizando composer

- composer config repositories.pagseguro-pagseguropayment git https://bitbucket.org/gingainterno/magento-v1-charge/src/master/ *OBS.: É NECESSÁRIO ALTERAR ESSE REPOSITÓRIO PARA UM OFICIAL DO PAGSEGURO*
- composer require pagseguro/pagseguropayment:dev-master

- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento setup:static-content:deploy pt_BR en_US

## Funcionalidades

### Logs

- No painel da loja é possível visualizar as interações da loja virtual com a API Charge do PagSeguro

### Pagamento com Boleto

- Fatura
- Estorno
- Atualização do status do pedido utilizando Cron
- Atualização de status do pedido utilziando callback

### Pagamento com Cartão de Crédito

- Autorização
- Fatura
- Estorno
- Pagamento com checkout transparente
- Pagamento com cartão salvo
- Atualização do status do pedido utilizando Cron
- Atualização de status do pedido utilziando callback
  \* Para pagamentos com dois cartões, caso um dos cartões não seja aprovado, o pedido será cancelado automaticamente e todos os estornos necessários serão realizados.

## Configurações

### Configurações Gerais

- **OAuth**: Botão de autenticação do seu login no PagSeguro
- **Ambiente de Teste**: Define se os pedidos realizados devem utilizar a API sandbox do PagSeguro
- **Validar Token**: Valida o Token inserido
- **Atributo de Tipo de Pessoa**: Atributo usado pela loja para definir o tipo de pessoa. Pode ser deixado vazio caso não exista.
- **Valor do Atributo para Pessoa Jurídica**: Caso a loja aceite pessoa jurídica, deve ser informado o valor do atributo
- **Atributo do CPF**: atributo de cliente para o CPF
- **Atributo do CNPJ**: atributo de cliente para o CNPJ
- **Habilitar Log**: caso esteja habilitado, o log das transações são salvos e disponibilizados em Vendas -> PagSeguro - Transações

### PagSeguro - Boleto

- **Habilitado**: Define se o método esta habilitado e deve ser exibido ao realizar o pedido
- **Título**: Título do método que será exibido para o cliente
- **Dias para o Boleto Expirar**: Dias para a data de expiração do boleto
- **Mensagem que Deve ser Exibida no Checkout**: Mensagem que o cliente irá ver ao selecionar o método
- **Status para Novo Pedido**: Status que os pedidos aguardando pagamento devem estar
- **Status para Pedido Pago**: Status que os pedidos pagos devem estar
- **Permir Estornar Valor Quando o Pedido for Cancelado**: Permitir o estorno ao cancelar um pedido, um memorando de crédito será criado na loja
- **Valor Mínimo do Pedido**: Valor mínimo para o método ser utilizado em um pedido, caso não tenha pode ser deixado vazio
- **Valor Máximo do Pedido**: Valor máximo para o método ser utilizado em um pedido, caso não tenha pode ser deixado vazio

### PagSeguro - Pix

- **Habilitado**: Define se o método esta habilitado e deve ser exibido ao realizar o pedido
- **Título**: Título do método que será exibido para o cliente
- **Dias para o QR COde Expirar**: Dias para a data de expiração do QR Code gerado para o PIX.
- **Mensagem que Deve ser Exibida no Checkout**: Mensagem que o cliente irá ver ao selecionar o método
- **Status para Novo Pedido**: Status que os pedidos aguardando pagamento devem estar
- **Status para Pedido Pago**: Status que os pedidos pagos devem estar
- **Permir Estornar Valor Quando o Pedido for Cancelado**: Permitir o estorno ao cancelar um pedido, um memorando de crédito será criado na loja
- **Valor Mínimo do Pedido**: Valor mínimo para o método ser utilizado em um pedido, caso não tenha pode ser deixado vazio
- **Valor Máximo do Pedido**: Valor máximo para o método ser utilizado em um pedido, caso não tenha pode ser deixado vazio

### PagSeguro - Cartão de Crédito

- **Habilitado**: Define se o método esta habilitado e deve ser exibido ao realizar o pedido
- **Título**: Título do método que será exibido para o cliente
- **Bandeiras Permitidas**: Define quais bandeiras de cartão podem ser aceitas
- **Ação de Pagamento**: Define se o pagamento será em um passo (captura) ou em dois (autorização)
- **Permitir que Clientes Salvem o Cartão de Crédito**: Permite que o cliente salve o cartão para utiliza-lo em compras futuras, o CVV do cartão não ficará salvo na loja, apenas o token retornado pelo PagSeguro será mantido no banco de dados
- **Usar Criptografia de Cartão do PagSeguro**: Os dados são criptografados pela biblioteca JS do PagSeguro antes de serem manipulados pela loja

### PagSeguro - Dois Cartões

- **Habilitado**: Define se o método esta habilitado e deve ser exibido ao realizar o pedido
- **Título**: Título do método que será exibido para o cliente
- **Bandeiras Permitidas**: Define quais bandeiras de cartão podem ser aceitas
- **Ação de Pagamento**: Define se o pagamento será em um passo (captura) ou em dois (autorização), o pagamento só será considerado válido caso os dois cartões tenham transações com sucesso, caso algum problema ocorra em um deles, qualquer valor cobrado é estornado para o cliente e o pedido é cancelado
- **Permitir que Clientes Salvem o Cartão de Crédito**: Permite que o cliente salve o cartão para utiliza-lo em compras futuras, o CVV do cartão não ficará salvo na loja, apenas o token retornado pelo PagSeguro será mantido no banco de dados
- **Usar Criptografia de Cartão do PagSeguro**: Os dados são criptografados pela biblioteca JS do PagSeguro antes de serem manipulados pela loja
