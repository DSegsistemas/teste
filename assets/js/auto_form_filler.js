/**
 * SISTEMA DE PREENCHIMENTO AUTOMÁTICO DE FORMULÁRIOS
 * 
 * Este arquivo contém o sistema completo de preenchimento automático
 * de formulários, compatível com qualquer tecnologia backend (PHP, ASP, etc.)
 * 
 * Autor: James - Equipe WORKENTERPRISE
 * Data: 2024
 * 
 * Funcionalidades:
 * - Detecção automática de formulários
 * - Análise inteligente de campos
 * - Preenchimento baseado em templates
 * - Compatibilidade com ASP.NET, PHP, HTML puro
 * - Sistema de validação integrado
 * - Interface de usuário intuitiva
 * - Acessibilidade completa
 * - Logs detalhados para debugging
 */

'use strict';

/**
 * CLASSE PRINCIPAL: AutoFormFiller
 * 
 * Gerencia todo o sistema de preenchimento automático de formulários
 */
class AutoFormFiller {
    
    /**
     * CONSTRUTOR
     * Inicializa o sistema de preenchimento automático
     */
    constructor() {
        // Configurações do sistema
        this.config = {
            // Seletores para detecção de formulários
            seletoresFormulario: [
                'form',
                '[role="form"]',
                '.form',
                '.formulario'
            ],
            
            // Tipos de campo suportados
            tiposCampo: {
                texto: ['text', 'search', 'url'],
                email: ['email'],
                telefone: ['tel'],
                senha: ['password'],
                numero: ['number'],
                data: ['date', 'datetime-local', 'time'],
                selecao: ['select-one', 'select-multiple'],
                checkbox: ['checkbox'],
                radio: ['radio'],
                textarea: ['textarea'],
                arquivo: ['file']
            },
            
            // Padrões para identificação de campos
            padroesCampos: {
                nome: /nome|name|first.*name|given.*name/i,
                sobrenome: /sobrenome|surname|last.*name|family.*name/i,
                nomeCompleto: /nome.*completo|full.*name|complete.*name/i,
                email: /email|e-mail|mail/i,
                telefone: /telefone|phone|tel|celular|mobile/i,
                endereco: /endereco|address|rua|street/i,
                cidade: /cidade|city/i,
                estado: /estado|state|uf/i,
                cep: /cep|zip.*code|postal.*code/i,
                cpf: /cpf|tax.*id/i,
                cnpj: /cnpj|company.*id/i,
                nascimento: /nascimento|birth.*date|data.*nasc/i,
                empresa: /empresa|company|organization/i,
                cargo: /cargo|position|job.*title/i,
                observacoes: /observ|comment|note|message/i
            },
            
            // Configurações de timing
            timing: {
                deteccaoFormulario: 1000,
                preenchimentoCampo: 100,
                validacaoDelay: 500,
                animacaoVelocidade: 300
            },
            
            // Configurações de compatibilidade ASP.NET
            aspNet: {
                aguardarViewState: true,
                dispararEventos: true,
                validarClientSide: true,
                timeoutCarregamento: 5000
            }
        };
        
        // Estado do sistema
        this.estado = {
            ativo: false,
            formularioAtual: null,
            camposDetectados: [],
            templateAtual: null,
            preenchendoAutomaticamente: false,
            logAtivado: true
        };
        
        // Templates de dados
        this.templates = {
            pessoaFisica: {
                nome: 'João',
                sobrenome: 'Silva',
                nomeCompleto: 'João Silva Santos',
                email: 'joao.silva@email.com',
                telefone: '(11) 99999-9999',
                endereco: 'Rua das Flores, 123',
                cidade: 'São Paulo',
                estado: 'SP',
                cep: '01234-567',
                cpf: '123.456.789-00',
                nascimento: '1990-01-15',
                observacoes: 'Dados de exemplo para teste'
            },
            
            pessoaJuridica: {
                empresa: 'Empresa Exemplo Ltda',
                cnpj: '12.345.678/0001-90',
                email: 'contato@empresa.com.br',
                telefone: '(11) 3333-4444',
                endereco: 'Av. Paulista, 1000',
                cidade: 'São Paulo',
                estado: 'SP',
                cep: '01310-100',
                observacoes: 'Empresa de exemplo para demonstração'
            },
            
            desenvolvedor: {
                nome: 'James',
                sobrenome: 'Developer',
                nomeCompleto: 'James Developer',
                email: 'james@workenterprise.com',
                telefone: '(11) 98765-4321',
                empresa: 'WORKENTERPRISE',
                cargo: 'Gerente Técnico',
                endereco: 'Rua da Tecnologia, 456',
                cidade: 'São Paulo',
                estado: 'SP',
                cep: '04567-890',
                observacoes: 'Desenvolvedor especialista em sistemas'
            }
        };
        
        // Inicializar sistema
        this.init();
    }
    
    /**
     * MÉTODO: init
     * Inicializa o sistema de preenchimento automático
     */
    init() {
        this.log('🚀 Inicializando AutoFormFiller...');
        
        // Aguardar carregamento completo da página
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.iniciarSistema();
            });
        } else {
            this.iniciarSistema();
        }
    }
    
    /**
     * MÉTODO: iniciarSistema
     * Inicia o sistema após carregamento da página
     */
    iniciarSistema() {
        // Aguardar um pouco para ASP.NET carregar completamente
        setTimeout(() => {
            this.criarInterface();
            this.detectarFormularios();
            this.configurarEventos();
            this.estado.ativo = true;
            
            this.log('✅ AutoFormFiller inicializado com sucesso!');
        }, this.config.timing.deteccaoFormulario);
    }
    
    /**
     * MÉTODO: criarInterface
     * Cria a interface de usuário do sistema
     */
    criarInterface() {
        this.log('🎨 Criando interface de usuário...');
        
        // Verificar se já existe
        if (document.getElementById('auto-form-filler-ui')) {
            return;
        }
        
        // Criar container principal
        const container = document.createElement('div');
        container.id = 'auto-form-filler-ui';
        container.className = 'auto-form-filler-container';
        
        // HTML da interface
        container.innerHTML = `
            <div class="auto-form-filler-panel">
                <div class="panel-header">
                    <h5 class="panel-title">
                        <i class="fas fa-magic me-2"></i>
                        Preenchimento Automático
                    </h5>
                    <button type="button" class="btn-close-panel" aria-label="Fechar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="panel-body">
                    <!-- Status do sistema -->
                    <div class="status-section mb-3">
                        <div class="status-indicator">
                            <span class="status-dot"></span>
                            <span class="status-text">Aguardando...</span>
                        </div>
                    </div>
                    
                    <!-- Seleção de template -->
                    <div class="template-section mb-3">
                        <label class="form-label">Template de Dados:</label>
                        <select class="form-select template-selector">
                            <option value="">Selecione um template</option>
                            <option value="pessoaFisica">Pessoa Física</option>
                            <option value="pessoaJuridica">Pessoa Jurídica</option>
                            <option value="desenvolvedor">Desenvolvedor</option>
                        </select>
                    </div>
                    
                    <!-- Botões de ação -->
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary btn-sm btn-detectar">
                            <i class="fas fa-search me-1"></i>
                            Detectar Formulários
                        </button>
                        
                        <button type="button" class="btn btn-success btn-sm btn-preencher" disabled>
                            <i class="fas fa-magic me-1"></i>
                            Preencher Automaticamente
                        </button>
                        
                        <button type="button" class="btn btn-warning btn-sm btn-limpar" disabled>
                            <i class="fas fa-eraser me-1"></i>
                            Limpar Campos
                        </button>
                    </div>
                    
                    <!-- Informações dos campos detectados -->
                    <div class="campos-detectados mt-3" style="display: none;">
                        <h6>Campos Detectados:</h6>
                        <div class="campos-lista"></div>
                    </div>
                    
                    <!-- Log de atividades -->
                    <div class="log-section mt-3">
                        <div class="log-header">
                            <small class="text-muted">Log de Atividades:</small>
                            <button type="button" class="btn btn-link btn-sm p-0 btn-toggle-log">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="log-content" style="display: none;">
                            <div class="log-messages"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Adicionar estilos CSS
        this.adicionarEstilos();
        
        // Adicionar ao DOM
        document.body.appendChild(container);
        
        // Configurar eventos da interface
        this.configurarEventosInterface();
        
        // Tornar arrastável
        this.tornarArrastavel(container);
    }
    
    /**
     * MÉTODO: adicionarEstilos
     * Adiciona estilos CSS para a interface
     */
    adicionarEstilos() {
        // Verificar se já existe
        if (document.getElementById('auto-form-filler-styles')) {
            return;
        }
        
        const estilos = document.createElement('style');
        estilos.id = 'auto-form-filler-styles';
        estilos.textContent = `
            .auto-form-filler-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 999999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                font-size: 14px;
            }
            
            .auto-form-filler-panel {
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                width: 320px;
                max-height: 80vh;
                overflow-y: auto;
            }
            
            .panel-header {
                background: linear-gradient(135deg, #007bff, #0056b3);
                color: white;
                padding: 12px 16px;
                border-radius: 8px 8px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                cursor: move;
            }
            
            .panel-title {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
            }
            
            .btn-close-panel {
                background: none;
                border: none;
                color: white;
                font-size: 16px;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                transition: background-color 0.2s;
            }
            
            .btn-close-panel:hover {
                background-color: rgba(255, 255, 255, 0.2);
            }
            
            .panel-body {
                padding: 16px;
            }
            
            .status-indicator {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background-color: #ffc107;
                animation: pulse 2s infinite;
            }
            
            .status-dot.ativo {
                background-color: #28a745;
            }
            
            .status-dot.erro {
                background-color: #dc3545;
            }
            
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
            
            .form-label {
                font-weight: 600;
                margin-bottom: 4px;
                display: block;
            }
            
            .form-select {
                width: 100%;
                padding: 6px 12px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                font-size: 14px;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            .btn {
                padding: 8px 12px;
                border: none;
                border-radius: 4px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                text-align: center;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            
            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            .btn-primary {
                background-color: #007bff;
                color: white;
            }
            
            .btn-primary:hover:not(:disabled) {
                background-color: #0056b3;
            }
            
            .btn-success {
                background-color: #28a745;
                color: white;
            }
            
            .btn-success:hover:not(:disabled) {
                background-color: #1e7e34;
            }
            
            .btn-warning {
                background-color: #ffc107;
                color: #212529;
            }
            
            .btn-warning:hover:not(:disabled) {
                background-color: #e0a800;
            }
            
            .campos-lista {
                max-height: 150px;
                overflow-y: auto;
                border: 1px solid #e9ecef;
                border-radius: 4px;
                padding: 8px;
                background-color: #f8f9fa;
            }
            
            .campo-item {
                padding: 4px 0;
                border-bottom: 1px solid #e9ecef;
                font-size: 12px;
            }
            
            .campo-item:last-child {
                border-bottom: none;
            }
            
            .log-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                cursor: pointer;
            }
            
            .log-content {
                margin-top: 8px;
                max-height: 120px;
                overflow-y: auto;
                border: 1px solid #e9ecef;
                border-radius: 4px;
                padding: 8px;
                background-color: #f8f9fa;
                font-family: 'Courier New', monospace;
                font-size: 11px;
            }
            
            .log-message {
                margin-bottom: 4px;
                color: #495057;
            }
            
            .log-message.success {
                color: #28a745;
            }
            
            .log-message.error {
                color: #dc3545;
            }
            
            .log-message.warning {
                color: #ffc107;
            }
            
            .campo-preenchido {
                animation: preenchimentoDestaque 1s ease-in-out;
            }
            
            @keyframes preenchimentoDestaque {
                0% { background-color: #fff3cd; }
                50% { background-color: #ffeaa7; }
                100% { background-color: white; }
            }
        `;
        
        document.head.appendChild(estilos);
     }
     
     /**
      * MÉTODO: configurarEventosInterface
      * Configura eventos da interface de usuário
      */
     configurarEventosInterface() {
         const container = document.getElementById('auto-form-filler-ui');
         
         // Botão fechar
         const btnFechar = container.querySelector('.btn-close-panel');
         btnFechar.addEventListener('click', () => {
             this.ocultarInterface();
         });
         
         // Botão detectar formulários
         const btnDetectar = container.querySelector('.btn-detectar');
         btnDetectar.addEventListener('click', () => {
             this.detectarFormularios();
         });
         
         // Botão preencher
         const btnPreencher = container.querySelector('.btn-preencher');
         btnPreencher.addEventListener('click', () => {
             this.preencherFormularioAutomaticamente();
         });
         
         // Botão limpar
         const btnLimpar = container.querySelector('.btn-limpar');
         btnLimpar.addEventListener('click', () => {
             this.limparCamposFormulario();
         });
         
         // Seletor de template
         const seletorTemplate = container.querySelector('.template-selector');
         seletorTemplate.addEventListener('change', (e) => {
             this.estado.templateAtual = e.target.value;
             this.log(`📋 Template selecionado: ${e.target.value}`);
         });
         
         // Toggle do log
         const btnToggleLog = container.querySelector('.btn-toggle-log');
         const logContent = container.querySelector('.log-content');
         
         btnToggleLog.addEventListener('click', () => {
             const isVisible = logContent.style.display !== 'none';
             logContent.style.display = isVisible ? 'none' : 'block';
             btnToggleLog.querySelector('i').className = isVisible ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
         });
     }
     
     /**
      * MÉTODO: tornarArrastavel
      * Torna a interface arrastável
      * @param {HTMLElement} container - Container da interface
      */
     tornarArrastavel(container) {
         const header = container.querySelector('.panel-header');
         let isDragging = false;
         let currentX;
         let currentY;
         let initialX;
         let initialY;
         let xOffset = 0;
         let yOffset = 0;
         
         header.addEventListener('mousedown', dragStart);
         document.addEventListener('mousemove', drag);
         document.addEventListener('mouseup', dragEnd);
         
         function dragStart(e) {
             initialX = e.clientX - xOffset;
             initialY = e.clientY - yOffset;
             
             if (e.target === header || header.contains(e.target)) {
                 isDragging = true;
             }
         }
         
         function drag(e) {
             if (isDragging) {
                 e.preventDefault();
                 currentX = e.clientX - initialX;
                 currentY = e.clientY - initialY;
                 
                 xOffset = currentX;
                 yOffset = currentY;
                 
                 container.style.transform = `translate3d(${currentX}px, ${currentY}px, 0)`;
             }
         }
         
         function dragEnd() {
             initialX = currentX;
             initialY = currentY;
             isDragging = false;
         }
     }
     
     /**
      * MÉTODO: detectarFormularios
      * Detecta formulários na página atual
      */
     detectarFormularios() {
         this.log('🔍 Iniciando detecção de formulários...');
         this.atualizarStatus('Detectando formulários...', 'ativo');
         
         // Buscar formulários
         const formularios = [];
         
         this.config.seletoresFormulario.forEach(seletor => {
             const elementos = document.querySelectorAll(seletor);
             elementos.forEach(elemento => {
                 if (!formularios.includes(elemento)) {
                     formularios.push(elemento);
                 }
             });
         });
         
         if (formularios.length === 0) {
             this.log('❌ Nenhum formulário encontrado na página', 'error');
             this.atualizarStatus('Nenhum formulário encontrado', 'erro');
             return;
         }
         
         // Analisar o primeiro formulário encontrado
         this.estado.formularioAtual = formularios[0];
         this.analisarCamposFormulario(this.estado.formularioAtual);
         
         this.log(`✅ ${formularios.length} formulário(s) encontrado(s)`, 'success');
         this.atualizarStatus(`${formularios.length} formulário(s) detectado(s)`, 'ativo');
         
         // Habilitar botões
         this.habilitarBotoes();
     }
     
     /**
      * MÉTODO: analisarCamposFormulario
      * Analisa os campos de um formulário
      * @param {HTMLElement} formulario - Formulário a ser analisado
      */
     analisarCamposFormulario(formulario) {
         this.log('🔬 Analisando campos do formulário...');
         
         const campos = formulario.querySelectorAll(
             'input, select, textarea'
         );
         
         this.estado.camposDetectados = [];
         
         campos.forEach(campo => {
             // Pular campos ocultos e botões
             if (campo.type === 'hidden' || 
                 campo.type === 'submit' || 
                 campo.type === 'button' || 
                 campo.type === 'reset') {
                 return;
             }
             
             const infoCampo = this.analisarCampo(campo);
             if (infoCampo) {
                 this.estado.camposDetectados.push(infoCampo);
             }
         });
         
         this.log(`📊 ${this.estado.camposDetectados.length} campos analisados`);
         this.exibirCamposDetectados();
     }
     
     /**
      * MÉTODO: analisarCampo
      * Analisa um campo específico
      * @param {HTMLElement} campo - Campo a ser analisado
      * @returns {Object|null} Informações do campo
      */
     analisarCampo(campo) {
         const info = {
             elemento: campo,
             id: campo.id,
             name: campo.name,
             type: campo.type || campo.tagName.toLowerCase(),
             label: this.obterLabelCampo(campo),
             placeholder: campo.placeholder,
             required: campo.required,
             tipoDado: null
         };
         
         // Identificar tipo de dado baseado em padrões
         info.tipoDado = this.identificarTipoDado(info);
         
         return info;
     }
     
     /**
      * MÉTODO: obterLabelCampo
      * Obtém o label associado a um campo
      * @param {HTMLElement} campo - Campo
      * @returns {string} Texto do label
      */
     obterLabelCampo(campo) {
         // Buscar label por 'for'
         if (campo.id) {
             const label = document.querySelector(`label[for="${campo.id}"]`);
             if (label) {
                 return label.textContent.trim();
             }
         }
         
         // Buscar label pai
         const labelPai = campo.closest('label');
         if (labelPai) {
             return labelPai.textContent.replace(campo.value || '', '').trim();
         }
         
         // Buscar label anterior
         let anterior = campo.previousElementSibling;
         while (anterior) {
             if (anterior.tagName === 'LABEL') {
                 return anterior.textContent.trim();
             }
             anterior = anterior.previousElementSibling;
         }
         
         return '';
     }
     
     /**
      * MÉTODO: identificarTipoDado
      * Identifica o tipo de dado baseado em padrões
      * @param {Object} infoCampo - Informações do campo
      * @returns {string} Tipo de dado identificado
      */
     identificarTipoDado(infoCampo) {
         const textoAnalise = [
             infoCampo.id,
             infoCampo.name,
             infoCampo.label,
             infoCampo.placeholder
         ].join(' ').toLowerCase();
         
         // Verificar padrões
         for (const [tipo, padrao] of Object.entries(this.config.padroesCampos)) {
             if (padrao.test(textoAnalise)) {
                 return tipo;
             }
         }
         
         // Verificar por tipo HTML
         if (infoCampo.type === 'email') return 'email';
         if (infoCampo.type === 'tel') return 'telefone';
         if (infoCampo.type === 'date') return 'nascimento';
         
         return 'texto';
      }
      
      /**
       * MÉTODO: exibirCamposDetectados
       * Exibe os campos detectados na interface
       */
      exibirCamposDetectados() {
          const container = document.querySelector('.campos-detectados');
          const lista = container.querySelector('.campos-lista');
          
          if (this.estado.camposDetectados.length === 0) {
              container.style.display = 'none';
              return;
          }
          
          lista.innerHTML = '';
          
          this.estado.camposDetectados.forEach(campo => {
              const item = document.createElement('div');
              item.className = 'campo-item';
              item.innerHTML = `
                  <strong>${campo.tipoDado}</strong>: ${campo.label || campo.id || campo.name}
                  <br><small class="text-muted">${campo.type} - ${campo.required ? 'Obrigatório' : 'Opcional'}</small>
              `;
              lista.appendChild(item);
          });
          
          container.style.display = 'block';
      }
      
      /**
       * MÉTODO: preencherFormularioAutomaticamente
       * Preenche o formulário automaticamente com base no template
       */
      preencherFormularioAutomaticamente() {
          if (!this.estado.templateAtual) {
              this.log('⚠️ Nenhum template selecionado', 'warning');
              return;
          }
          
          if (!this.estado.formularioAtual) {
              this.log('⚠️ Nenhum formulário detectado', 'warning');
              return;
          }
          
          this.log('🎯 Iniciando preenchimento automático...');
          this.atualizarStatus('Preenchendo formulário...', 'ativo');
          
          const template = this.templates[this.estado.templateAtual];
          let camposPreenchidos = 0;
          
          this.estado.camposDetectados.forEach((campo, index) => {
              setTimeout(() => {
                  if (this.preencherCampo(campo, template)) {
                      camposPreenchidos++;
                  }
                  
                  // Verificar se terminou
                  if (index === this.estado.camposDetectados.length - 1) {
                      this.log(`✅ Preenchimento concluído: ${camposPreenchidos} campos preenchidos`, 'success');
                      this.atualizarStatus(`${camposPreenchidos} campos preenchidos`, 'ativo');
                  }
              }, index * this.config.timing.preenchimentoCampo);
          });
      }
      
      /**
       * MÉTODO: preencherCampo
       * Preenche um campo específico
       * @param {Object} infoCampo - Informações do campo
       * @param {Object} template - Template de dados
       * @returns {boolean} Sucesso no preenchimento
       */
      preencherCampo(infoCampo, template) {
          const campo = infoCampo.elemento;
          const valor = template[infoCampo.tipoDado];
          
          if (!valor) {
              return false;
          }
          
          try {
              // Focar no campo
              campo.focus();
              
              // Preencher baseado no tipo
              if (campo.type === 'checkbox') {
                  campo.checked = true;
              } else if (campo.type === 'radio') {
                  campo.checked = true;
              } else if (campo.tagName === 'SELECT') {
                  // Tentar encontrar opção correspondente
                  const opcoes = Array.from(campo.options);
                  const opcaoCorrespondente = opcoes.find(opcao => 
                      opcao.text.toLowerCase().includes(valor.toLowerCase()) ||
                      opcao.value.toLowerCase().includes(valor.toLowerCase())
                  );
                  
                  if (opcaoCorrespondente) {
                      campo.value = opcaoCorrespondente.value;
                  }
              } else {
                  // Campo de texto
                  campo.value = valor;
              }
              
              // Disparar eventos para compatibilidade
              this.dispararEventosCampo(campo);
              
              // Adicionar efeito visual
              campo.classList.add('campo-preenchido');
              setTimeout(() => {
                  campo.classList.remove('campo-preenchido');
              }, 1000);
              
              return true;
              
          } catch (error) {
              this.log(`❌ Erro ao preencher campo ${infoCampo.id}: ${error.message}`, 'error');
              return false;
          }
      }
      
      /**
       * MÉTODO: dispararEventosCampo
       * Dispara eventos necessários para compatibilidade
       * @param {HTMLElement} campo - Campo que foi preenchido
       */
      dispararEventosCampo(campo) {
          // Eventos básicos
          const eventos = ['input', 'change', 'blur'];
          
          eventos.forEach(tipoEvento => {
              const evento = new Event(tipoEvento, {
                  bubbles: true,
                  cancelable: true
              });
              campo.dispatchEvent(evento);
          });
          
          // Para compatibilidade com ASP.NET
          if (this.config.aspNet.dispararEventos) {
              // Disparar eventos específicos do ASP.NET
              if (typeof __doPostBack === 'function') {
                  // Simular eventos do ASP.NET se necessário
              }
          }
      }
      
      /**
       * MÉTODO: limparCamposFormulario
       * Limpa todos os campos do formulário
       */
      limparCamposFormulario() {
          if (!this.estado.formularioAtual) {
              this.log('⚠️ Nenhum formulário detectado', 'warning');
              return;
          }
          
          this.log('🧹 Limpando campos do formulário...');
          
          this.estado.camposDetectados.forEach(infoCampo => {
              const campo = infoCampo.elemento;
              
              if (campo.type === 'checkbox' || campo.type === 'radio') {
                  campo.checked = false;
              } else {
                  campo.value = '';
              }
              
              // Disparar eventos
              this.dispararEventosCampo(campo);
          });
          
          this.log('✅ Campos limpos com sucesso', 'success');
      }
      
      /**
       * MÉTODO: habilitarBotoes
       * Habilita os botões da interface
       */
      habilitarBotoes() {
          const container = document.getElementById('auto-form-filler-ui');
          const btnPreencher = container.querySelector('.btn-preencher');
          const btnLimpar = container.querySelector('.btn-limpar');
          
          btnPreencher.disabled = false;
          btnLimpar.disabled = false;
      }
      
      /**
       * MÉTODO: atualizarStatus
       * Atualiza o status na interface
       * @param {string} texto - Texto do status
       * @param {string} tipo - Tipo do status (ativo, erro, etc.)
       */
      atualizarStatus(texto, tipo = '') {
          const container = document.getElementById('auto-form-filler-ui');
          if (!container) return;
          
          const statusText = container.querySelector('.status-text');
          const statusDot = container.querySelector('.status-dot');
          
          if (statusText) {
              statusText.textContent = texto;
          }
          
          if (statusDot) {
              statusDot.className = `status-dot ${tipo}`;
          }
      }
      
      /**
       * MÉTODO: log
       * Adiciona mensagem ao log
       * @param {string} mensagem - Mensagem do log
       * @param {string} tipo - Tipo da mensagem
       */
      log(mensagem, tipo = 'info') {
          if (!this.estado.logAtivado) return;
          
          console.log(`[AutoFormFiller] ${mensagem}`);
          
          const container = document.getElementById('auto-form-filler-ui');
          if (!container) return;
          
          const logMessages = container.querySelector('.log-messages');
          if (!logMessages) return;
          
          const timestamp = new Date().toLocaleTimeString();
          const logItem = document.createElement('div');
          logItem.className = `log-message ${tipo}`;
          logItem.textContent = `[${timestamp}] ${mensagem}`;
          
          logMessages.appendChild(logItem);
          
          // Manter apenas as últimas 50 mensagens
          while (logMessages.children.length > 50) {
              logMessages.removeChild(logMessages.firstChild);
          }
          
          // Scroll para a última mensagem
          logMessages.scrollTop = logMessages.scrollHeight;
      }
      
      /**
       * MÉTODO: ocultarInterface
       * Oculta a interface do sistema
       */
      ocultarInterface() {
          const container = document.getElementById('auto-form-filler-ui');
          if (container) {
              container.style.display = 'none';
          }
      }
      
      /**
       * MÉTODO: mostrarInterface
       * Mostra a interface do sistema
       */
      mostrarInterface() {
          const container = document.getElementById('auto-form-filler-ui');
          if (container) {
              container.style.display = 'block';
          }
      }
      
      /**
       * MÉTODO: configurarEventos
       * Configura eventos globais do sistema
       */
      configurarEventos() {
          // Atalho de teclado para mostrar/ocultar interface
          document.addEventListener('keydown', (e) => {
              // Ctrl + Shift + F para toggle da interface
              if (e.ctrlKey && e.shiftKey && e.key === 'F') {
                  e.preventDefault();
                  const container = document.getElementById('auto-form-filler-ui');
                  if (container) {
                      const isVisible = container.style.display !== 'none';
                      if (isVisible) {
                          this.ocultarInterface();
                      } else {
                          this.mostrarInterface();
                      }
                  }
              }
          });
          
          // Detectar mudanças na página (para SPAs)
          const observer = new MutationObserver(() => {
              if (this.estado.ativo) {
                  // Redetectar formulários após mudanças
                  setTimeout(() => {
                      this.detectarFormularios();
                  }, 1000);
              }
          });
          
          observer.observe(document.body, {
              childList: true,
              subtree: true
          });
      }
  }
  
  /**
   * INICIALIZAÇÃO GLOBAL
   * Cria instância global do sistema
   */
  window.AutoFormFiller = AutoFormFiller;
  
  // Inicializar automaticamente
  document.addEventListener('DOMContentLoaded', () => {
      console.log('🌟 Iniciando AutoFormFiller...');
      window.autoFormFiller = new AutoFormFiller();
  });
  
  // Para compatibilidade com carregamento tardio
  if (document.readyState !== 'loading') {
      console.log('🌟 Iniciando AutoFormFiller (carregamento tardio)...');
      window.autoFormFiller = new AutoFormFiller();
  }