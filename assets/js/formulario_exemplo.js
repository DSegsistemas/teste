/**
 * JAVASCRIPT PARA O FORMULÁRIO DE EXEMPLO
 * 
 * Este arquivo contém todas as funcionalidades JavaScript para o formulário
 * de exemplo do sistema de preenchimento automático de formulários.
 * 
 * Autor: James - Equipe WORKENTERPRISE
 * Data: 2024
 * 
 * Funcionalidades:
 * - Validação em tempo real
 * - Máscaras de entrada
 * - Contador de caracteres
 * - Animações e transições
 * - Integração com o sistema de preenchimento automático
 * - Acessibilidade (WCAG 2.1)
 * - Responsividade
 */

'use strict';

/**
 * CLASSE PRINCIPAL: FormularioExemplo
 * 
 * Gerencia todas as funcionalidades do formulário de exemplo
 */
class FormularioExemplo {
    
    /**
     * CONSTRUTOR
     * Inicializa a classe e configura os elementos
     */
    constructor() {
        // Elementos principais
        this.formulario = document.getElementById('exemplo-formulario');
        this.areaResultado = document.getElementById('resultado-formulario');
        
        // Configurações
        this.config = {
            limiteCaracteres: 500,
            tempoProcessamento: 2000,
            animacaoVelocidade: 300
        };
        
        // Estado do formulário
        this.estado = {
            processando: false,
            validado: false,
            enviado: false
        };
        
        // Inicializar funcionalidades
        this.init();
    }
    
    /**
     * MÉTODO: init
     * Inicializa todas as funcionalidades do formulário
     */
    init() {
        console.log('🚀 Inicializando FormularioExemplo...');
        
        // Verificar se o formulário existe
        if (!this.formulario) {
            console.error('❌ Formulário não encontrado!');
            return;
        }
        
        // Configurar funcionalidades
        this.configurarMascaras();
        this.configurarValidacao();
        this.configurarContadores();
        this.configurarEventos();
        this.configurarAcessibilidade();
        this.configurarAnimacoes();
        
        // Auto-foco no primeiro campo
        this.focarPrimeiroCampo();
        
        console.log('✅ FormularioExemplo inicializado com sucesso!');
    }
    
    /**
     * MÉTODO: configurarMascaras
     * Aplica máscaras de entrada nos campos
     */
    configurarMascaras() {
        console.log('🎭 Configurando máscaras de entrada...');
        
        // Máscara de telefone
        const campoTelefone = document.getElementById('telefone');
        if (campoTelefone) {
            this.aplicarMascaraTelefone(campoTelefone);
        }
        
        // Máscara de CEP
        const campoCEP = document.getElementById('cep');
        if (campoCEP) {
            this.aplicarMascaraCEP(campoCEP);
        }
        
        // Máscara de nome (apenas letras e espaços)
        const campoNome = document.getElementById('nome_completo');
        if (campoNome) {
            this.aplicarMascaraNome(campoNome);
        }
    }
    
    /**
     * MÉTODO: aplicarMascaraTelefone
     * Aplica máscara brasileira de telefone
     * @param {HTMLElement} campo - Campo de telefone
     */
    aplicarMascaraTelefone(campo) {
        campo.addEventListener('input', (e) => {
            let valor = e.target.value.replace(/\D/g, '');
            
            // Limitar a 11 dígitos
            if (valor.length > 11) {
                valor = valor.substring(0, 11);
            }
            
            // Aplicar máscara
            if (valor.length <= 11) {
                valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
                valor = valor.replace(/(\d{4,5})(\d{4})$/, '$1-$2');
            }
            
            e.target.value = valor;
            
            // Validar formato
            this.validarTelefone(campo, valor);
        });
        
        // Placeholder dinâmico
        campo.addEventListener('focus', () => {
            if (!campo.value) {
                campo.placeholder = '(11) 99999-9999';
            }
        });
        
        campo.addEventListener('blur', () => {
            if (!campo.value) {
                campo.placeholder = 'Digite seu telefone';
            }
        });
    }
    
    /**
     * MÉTODO: aplicarMascaraCEP
     * Aplica máscara brasileira de CEP
     * @param {HTMLElement} campo - Campo de CEP
     */
    aplicarMascaraCEP(campo) {
        campo.addEventListener('input', (e) => {
            let valor = e.target.value.replace(/\D/g, '');
            
            // Limitar a 8 dígitos
            if (valor.length > 8) {
                valor = valor.substring(0, 8);
            }
            
            // Aplicar máscara
            if (valor.length > 5) {
                valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
            }
            
            e.target.value = valor;
            
            // Buscar endereço automaticamente
            if (valor.length === 9) {
                this.buscarEnderecoPorCEP(valor.replace('-', ''));
            }
        });
    }
    
    /**
     * MÉTODO: aplicarMascaraNome
     * Permite apenas letras, espaços e acentos
     * @param {HTMLElement} campo - Campo de nome
     */
    aplicarMascaraNome(campo) {
        campo.addEventListener('input', (e) => {
            // Permitir apenas letras, espaços e acentos
            let valor = e.target.value.replace(/[^a-zA-ZÀ-ÿ\s]/g, '');
            
            // Capitalizar primeira letra de cada palavra
            valor = valor.replace(/\b\w/g, l => l.toUpperCase());
            
            e.target.value = valor;
        });
    }
    
    /**
     * MÉTODO: buscarEnderecoPorCEP
     * Busca endereço via API dos Correios
     * @param {string} cep - CEP para busca
     */
    async buscarEnderecoPorCEP(cep) {
        try {
            console.log(`🔍 Buscando endereço para CEP: ${cep}`);
            
            // Mostrar loading
            const campoCEP = document.getElementById('cep');
            const loadingIcon = this.criarIconeLoading();
            campoCEP.parentNode.appendChild(loadingIcon);
            
            // Fazer requisição para API
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const dados = await response.json();
            
            // Remover loading
            loadingIcon.remove();
            
            if (!dados.erro) {
                // Preencher campos automaticamente
                this.preencherEndereco(dados);
                
                // Mostrar notificação de sucesso
                this.mostrarNotificacao('Endereço encontrado!', 'success');
            } else {
                this.mostrarNotificacao('CEP não encontrado.', 'warning');
            }
            
        } catch (error) {
            console.error('❌ Erro ao buscar CEP:', error);
            this.mostrarNotificacao('Erro ao buscar CEP.', 'error');
        }
    }
    
    /**
     * MÉTODO: preencherEndereco
     * Preenche os campos de endereço com os dados da API
     * @param {Object} dados - Dados do endereço
     */
    preencherEndereco(dados) {
        const campos = {
            'rua': dados.logradouro,
            'cidade': dados.localidade,
            'estado': dados.uf
        };
        
        Object.entries(campos).forEach(([id, valor]) => {
            const campo = document.getElementById(id);
            if (campo && valor) {
                campo.value = valor;
                
                // Adicionar animação
                campo.classList.add('campo-preenchido');
                setTimeout(() => {
                    campo.classList.remove('campo-preenchido');
                }, 1000);
            }
        });
    }
    
    /**
     * MÉTODO: configurarValidacao
     * Configura validação em tempo real
     */
    configurarValidacao() {
        console.log('✅ Configurando validação em tempo real...');
        
        // Email
        const campoEmail = document.getElementById('email');
        if (campoEmail) {
            this.configurarValidacaoEmail(campoEmail);
        }
        
        // Campos obrigatórios
        const camposObrigatorios = this.formulario.querySelectorAll('[required]');
        camposObrigatorios.forEach(campo => {
            this.configurarValidacaoCampo(campo);
        });
    }
    
    /**
     * MÉTODO: configurarValidacaoEmail
     * Configura validação específica para email
     * @param {HTMLElement} campo - Campo de email
     */
    configurarValidacaoEmail(campo) {
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        campo.addEventListener('blur', () => {
            const email = campo.value.trim();
            
            if (email) {
                if (regexEmail.test(email)) {
                    this.marcarCampoValido(campo);
                } else {
                    this.marcarCampoInvalido(campo, 'Por favor, insira um email válido.');
                }
            }
        });
        
        campo.addEventListener('input', () => {
            // Remover classes de validação durante digitação
            campo.classList.remove('is-valid', 'is-invalid');
        });
    }
    
    /**
     * MÉTODO: configurarValidacaoCampo
     * Configura validação para campos obrigatórios
     * @param {HTMLElement} campo - Campo a ser validado
     */
    configurarValidacaoCampo(campo) {
        campo.addEventListener('blur', () => {
            if (campo.hasAttribute('required')) {
                if (campo.value.trim()) {
                    this.marcarCampoValido(campo);
                } else {
                    this.marcarCampoInvalido(campo, 'Este campo é obrigatório.');
                }
            }
        });
        
        campo.addEventListener('input', () => {
            // Remover classes de validação durante digitação
            campo.classList.remove('is-valid', 'is-invalid');
        });
    }
    
    /**
     * MÉTODO: marcarCampoValido
     * Marca um campo como válido
     * @param {HTMLElement} campo - Campo a ser marcado
     */
    marcarCampoValido(campo) {
        campo.classList.remove('is-invalid');
        campo.classList.add('is-valid');
        
        // Remover mensagem de erro
        const feedback = campo.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.style.display = 'none';
        }
    }
    
    /**
     * MÉTODO: marcarCampoInvalido
     * Marca um campo como inválido
     * @param {HTMLElement} campo - Campo a ser marcado
     * @param {string} mensagem - Mensagem de erro
     */
    marcarCampoInvalido(campo, mensagem) {
        campo.classList.remove('is-valid');
        campo.classList.add('is-invalid');
        
        // Mostrar mensagem de erro
        let feedback = campo.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            campo.parentNode.appendChild(feedback);
        }
        
        feedback.textContent = mensagem;
        feedback.style.display = 'block';
    }
    
    /**
     * MÉTODO: configurarContadores
     * Configura contadores de caracteres
     */
    configurarContadores() {
        console.log('🔢 Configurando contadores de caracteres...');
        
        const campoObservacoes = document.getElementById('observacoes');
        if (campoObservacoes) {
            this.adicionarContadorCaracteres(campoObservacoes, this.config.limiteCaracteres);
        }
    }
    
    /**
     * MÉTODO: adicionarContadorCaracteres
     * Adiciona contador de caracteres a um campo
     * @param {HTMLElement} campo - Campo de texto
     * @param {number} limite - Limite de caracteres
     */
    adicionarContadorCaracteres(campo, limite) {
        // Criar elemento contador
        const contador = document.createElement('div');
        contador.className = 'character-counter form-text text-end';
        contador.style.fontSize = '0.875rem';
        
        // Inserir após o campo
        campo.parentNode.appendChild(contador);
        
        // Função para atualizar contador
        const atualizarContador = () => {
            const atual = campo.value.length;
            contador.textContent = `${atual}/${limite} caracteres`;
            
            // Remover classes anteriores
            contador.classList.remove('text-muted', 'text-warning', 'text-danger');
            
            // Aplicar cor baseada na porcentagem
            if (atual >= limite) {
                contador.classList.add('text-danger');
                campo.value = campo.value.substring(0, limite); // Limitar caracteres
            } else if (atual > limite * 0.9) {
                contador.classList.add('text-warning');
            } else {
                contador.classList.add('text-muted');
            }
        };
        
        // Eventos
        campo.addEventListener('input', atualizarContador);
        campo.addEventListener('paste', () => {
            setTimeout(atualizarContador, 10);
        });
        
        // Inicializar contador
        atualizarContador();
    }
    
    /**
     * MÉTODO: configurarEventos
     * Configura eventos do formulário
     */
    configurarEventos() {
        console.log('🎯 Configurando eventos do formulário...');
        
        // Evento de submissão
        this.formulario.addEventListener('submit', (e) => {
            this.processarEnvio(e);
        });
        
        // Evento de reset
        this.formulario.addEventListener('reset', () => {
            this.processarReset();
        });
        
        // Eventos de seção (navegação suave)
        this.configurarNavegacaoSecoes();
    }
    
    /**
     * MÉTODO: processarEnvio
     * Processa o envio do formulário
     * @param {Event} e - Evento de submissão
     */
    processarEnvio(e) {
        e.preventDefault();
        
        console.log('📤 Processando envio do formulário...');
        
        // Verificar se já está processando
        if (this.estado.processando) {
            return;
        }
        
        // Adicionar classes de validação
        this.formulario.classList.add('was-validated');
        
        // Verificar validade
        if (this.formulario.checkValidity()) {
            this.enviarFormulario();
        } else {
            this.focarPrimeiroErro();
        }
    }
    
    /**
     * MÉTODO: enviarFormulario
     * Simula o envio do formulário
     */
    async enviarFormulario() {
        this.estado.processando = true;
        
        // Obter botão de envio
        const botaoEnvio = this.formulario.querySelector('button[type="submit"]');
        const textoOriginal = botaoEnvio.innerHTML;
        
        // Mostrar estado de carregamento
        botaoEnvio.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
        botaoEnvio.disabled = true;
        
        try {
            // Simular processamento
            await this.simularProcessamento();
            
            // Sucesso
            this.mostrarResultadoSucesso();
            
        } catch (error) {
            console.error('❌ Erro no envio:', error);
            this.mostrarNotificacao('Erro ao enviar formulário.', 'error');
            
        } finally {
            // Restaurar botão
            botaoEnvio.innerHTML = textoOriginal;
            botaoEnvio.disabled = false;
            this.estado.processando = false;
        }
    }
    
    /**
     * MÉTODO: simularProcessamento
     * Simula delay de processamento
     */
    simularProcessamento() {
        return new Promise(resolve => {
            setTimeout(resolve, this.config.tempoProcessamento);
        });
    }
    
    /**
     * MÉTODO: mostrarResultadoSucesso
     * Mostra resultado de sucesso
     */
    mostrarResultadoSucesso() {
        this.estado.enviado = true;
        
        // Mostrar área de resultado
        this.areaResultado.style.display = 'block';
        this.areaResultado.classList.add('fade-in');
        
        // Scroll suave para resultado
        this.areaResultado.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        
        // Limpar validação após sucesso
        setTimeout(() => {
            this.limparValidacao();
        }, 3000);
        
        console.log('✅ Formulário enviado com sucesso!');
    }
    
    /**
     * MÉTODO: processarReset
     * Processa o reset do formulário
     */
    processarReset() {
        console.log('🔄 Resetando formulário...');
        
        // Limpar validação
        this.limparValidacao();
        
        // Esconder resultado
        this.areaResultado.style.display = 'none';
        
        // Resetar estado
        this.estado = {
            processando: false,
            validado: false,
            enviado: false
        };
        
        // Focar primeiro campo
        setTimeout(() => {
            this.focarPrimeiroCampo();
        }, 100);
    }
    
    /**
     * MÉTODO: limparValidacao
     * Remove todas as classes de validação
     */
    limparValidacao() {
        this.formulario.classList.remove('was-validated');
        
        const campos = this.formulario.querySelectorAll('.form-control');
        campos.forEach(campo => {
            campo.classList.remove('is-valid', 'is-invalid');
        });
        
        // Esconder mensagens de erro
        const feedbacks = this.formulario.querySelectorAll('.invalid-feedback');
        feedbacks.forEach(feedback => {
            feedback.style.display = 'none';
        });
    }
    
    /**
     * MÉTODO: focarPrimeiroErro
     * Foca no primeiro campo com erro
     */
    focarPrimeiroErro() {
        const primeiroInvalido = this.formulario.querySelector(':invalid');
        if (primeiroInvalido) {
            primeiroInvalido.focus();
            primeiroInvalido.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }
    
    /**
     * MÉTODO: focarPrimeiroCampo
     * Foca no primeiro campo do formulário
     */
    focarPrimeiroCampo() {
        const primeiroCampo = document.getElementById('nome_completo');
        if (primeiroCampo) {
            primeiroCampo.focus();
        }
    }
    
    /**
     * MÉTODO: configurarNavegacaoSecoes
     * Configura navegação suave entre seções
     */
    configurarNavegacaoSecoes() {
        const titulosSecao = document.querySelectorAll('.section-title');
        
        titulosSecao.forEach(titulo => {
            titulo.style.cursor = 'pointer';
            titulo.setAttribute('tabindex', '0');
            titulo.setAttribute('role', 'button');
            titulo.setAttribute('aria-label', 'Navegar para esta seção');
            
            // Evento de clique
            titulo.addEventListener('click', () => {
                this.navegarParaSecao(titulo);
            });
            
            // Evento de teclado (Enter/Space)
            titulo.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.navegarParaSecao(titulo);
                }
            });
        });
    }
    
    /**
     * MÉTODO: navegarParaSecao
     * Navega suavemente para uma seção
     * @param {HTMLElement} titulo - Título da seção
     */
    navegarParaSecao(titulo) {
        titulo.parentElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        
        // Adicionar efeito visual
        titulo.parentElement.classList.add('secao-destacada');
        setTimeout(() => {
            titulo.parentElement.classList.remove('secao-destacada');
        }, 2000);
    }
    
    /**
     * MÉTODO: configurarAcessibilidade
     * Configura recursos de acessibilidade
     */
    configurarAcessibilidade() {
        console.log('♿ Configurando acessibilidade...');
        
        // Adicionar ARIA labels
        this.adicionarAriaLabels();
        
        // Configurar navegação por teclado
        this.configurarNavegacaoTeclado();
        
        // Anunciar mudanças para leitores de tela
        this.configurarAnuncios();
    }
    
    /**
     * MÉTODO: adicionarAriaLabels
     * Adiciona labels ARIA para acessibilidade
     */
    adicionarAriaLabels() {
        // Campos obrigatórios
        const camposObrigatorios = this.formulario.querySelectorAll('[required]');
        camposObrigatorios.forEach(campo => {
            campo.setAttribute('aria-required', 'true');
        });
        
        // Grupos de campos
        const gruposCampos = document.querySelectorAll('.field-group');
        gruposCampos.forEach((grupo, index) => {
            const titulo = grupo.querySelector('.section-title');
            if (titulo) {
                const id = `secao-${index}`;
                titulo.id = id;
                grupo.setAttribute('aria-labelledby', id);
                grupo.setAttribute('role', 'group');
            }
        });
    }
    
    /**
     * MÉTODO: configurarNavegacaoTeclado
     * Configura navegação por teclado
     */
    configurarNavegacaoTeclado() {
        // Atalhos de teclado
        document.addEventListener('keydown', (e) => {
            // Ctrl + Enter para enviar
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                this.formulario.dispatchEvent(new Event('submit'));
            }
            
            // Escape para limpar
            if (e.key === 'Escape') {
                this.formulario.reset();
            }
        });
    }
    
    /**
     * MÉTODO: configurarAnuncios
     * Configura anúncios para leitores de tela
     */
    configurarAnuncios() {
        // Criar região de anúncios
        const regiaoAnuncios = document.createElement('div');
        regiaoAnuncios.setAttribute('aria-live', 'polite');
        regiaoAnuncios.setAttribute('aria-atomic', 'true');
        regiaoAnuncios.className = 'sr-only';
        regiaoAnuncios.id = 'anuncios-acessibilidade';
        document.body.appendChild(regiaoAnuncios);
        
        this.regiaoAnuncios = regiaoAnuncios;
    }
    
    /**
     * MÉTODO: anunciar
     * Anuncia mensagem para leitores de tela
     * @param {string} mensagem - Mensagem a ser anunciada
     */
    anunciar(mensagem) {
        if (this.regiaoAnuncios) {
            this.regiaoAnuncios.textContent = mensagem;
        }
    }
    
    /**
     * MÉTODO: configurarAnimacoes
     * Configura animações e transições
     */
    configurarAnimacoes() {
        console.log('🎬 Configurando animações...');
        
        // Animação de entrada para seções
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('slide-up');
                }
            });
        }, {
            threshold: 0.1
        });
        
        // Observar seções
        const secoes = document.querySelectorAll('.field-group');
        secoes.forEach(secao => {
            observer.observe(secao);
        });
    }
    
    /**
     * MÉTODO: validarTelefone
     * Valida formato de telefone
     * @param {HTMLElement} campo - Campo de telefone
     * @param {string} valor - Valor do telefone
     */
    validarTelefone(campo, valor) {
        const regexTelefone = /^\(\d{2}\)\s\d{4,5}-\d{4}$/;
        
        if (valor && !regexTelefone.test(valor)) {
            this.marcarCampoInvalido(campo, 'Formato inválido. Use: (11) 99999-9999');
        } else if (valor) {
            this.marcarCampoValido(campo);
        }
    }
    
    /**
     * MÉTODO: criarIconeLoading
     * Cria ícone de loading
     * @returns {HTMLElement} Elemento de loading
     */
    criarIconeLoading() {
        const loading = document.createElement('div');
        loading.className = 'loading-indicator';
        loading.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        loading.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
        `;
        return loading;
    }
    
    /**
     * MÉTODO: mostrarNotificacao
     * Mostra notificação toast
     * @param {string} mensagem - Mensagem da notificação
     * @param {string} tipo - Tipo da notificação (success, warning, error)
     */
    mostrarNotificacao(mensagem, tipo = 'info') {
        // Criar elemento de notificação
        const notificacao = document.createElement('div');
        notificacao.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show`;
        notificacao.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        `;
        
        notificacao.innerHTML = `
            <i class="fas fa-${this.obterIconeNotificacao(tipo)} me-2"></i>
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Adicionar ao DOM
        document.body.appendChild(notificacao);
        
        // Remover automaticamente
        setTimeout(() => {
            if (notificacao.parentNode) {
                notificacao.remove();
            }
        }, 5000);
        
        // Anunciar para leitores de tela
        this.anunciar(mensagem);
    }
    
    /**
     * MÉTODO: obterIconeNotificacao
     * Obtém ícone baseado no tipo de notificação
     * @param {string} tipo - Tipo da notificação
     * @returns {string} Classe do ícone
     */
    obterIconeNotificacao(tipo) {
        const icones = {
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'times-circle',
            'info': 'info-circle'
        };
        
        return icones[tipo] || 'info-circle';
    }
    
    /**
     * MÉTODO: obterDadosFormulario
     * Obtém todos os dados do formulário
     * @returns {Object} Dados do formulário
     */
    obterDadosFormulario() {
        const formData = new FormData(this.formulario);
        const dados = {};
        
        for (let [key, value] of formData.entries()) {
            if (dados[key]) {
                // Campo com múltiplos valores (checkboxes)
                if (Array.isArray(dados[key])) {
                    dados[key].push(value);
                } else {
                    dados[key] = [dados[key], value];
                }
            } else {
                dados[key] = value;
            }
        }
        
        return dados;
    }
    
    /**
     * MÉTODO: preencherFormulario
     * Preenche o formulário com dados fornecidos
     * @param {Object} dados - Dados para preenchimento
     */
    preencherFormulario(dados) {
        console.log('📝 Preenchendo formulário automaticamente...', dados);
        
        Object.entries(dados).forEach(([campo, valor]) => {
            const elemento = document.getElementById(campo) || 
                           document.querySelector(`[name="${campo}"]`);
            
            if (elemento) {
                if (elemento.type === 'checkbox' || elemento.type === 'radio') {
                    if (Array.isArray(valor)) {
                        valor.forEach(v => {
                            const opcao = document.querySelector(`[name="${campo}"][value="${v}"]`);
                            if (opcao) opcao.checked = true;
                        });
                    } else {
                        elemento.checked = elemento.value === valor;
                    }
                } else {
                    elemento.value = valor;
                    
                    // Disparar evento de input para máscaras
                    elemento.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                // Adicionar efeito visual
                elemento.classList.add('campo-preenchido');
                setTimeout(() => {
                    elemento.classList.remove('campo-preenchido');
                }, 1000);
            }
        });
        
        this.anunciar('Formulário preenchido automaticamente');
    }
}

/**
 * INICIALIZAÇÃO
 * Inicializa a classe quando o DOM estiver carregado
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🌟 Iniciando sistema do formulário de exemplo...');
    
    // Criar instância global
    window.formularioExemplo = new FormularioExemplo();
    
    // Adicionar estilos CSS dinâmicos
    const estilosDinamicos = document.createElement('style');
    estilosDinamicos.textContent = `
        .campo-preenchido {
            animation: preenchimentoAutomatico 1s ease-in-out;
        }
        
        @keyframes preenchimentoAutomatico {
            0% { background-color: #fff3cd; }
            50% { background-color: #ffeaa7; }
            100% { background-color: white; }
        }
        
        .secao-destacada {
            animation: destacarSecao 2s ease-in-out;
        }
        
        @keyframes destacarSecao {
            0%, 100% { background-color: transparent; }
            50% { background-color: rgba(0, 123, 255, 0.1); }
        }
        
        .sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        
        .loading-indicator {
            pointer-events: none;
        }
    `;
    
    document.head.appendChild(estilosDinamicos);
    
    console.log('✨ Sistema do formulário de exemplo carregado com sucesso!');
});

/**
 * EXPORTAR PARA USO EXTERNO
 * Permite uso da classe em outros scripts
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormularioExemplo;
}