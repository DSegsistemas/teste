/**
 * Workshop - Preenchedor Automático de Formulários
 * Sistema para automatizar o preenchimento de formulários em sites externos
 * Autor: James - WORKENTERPRISE
 */

class FormAutoFiller {
    constructor() {
        this.config = {
            delay: 500, // Delay entre preenchimentos (ms)
            waitForElement: 5000, // Tempo máximo para aguardar elemento (ms)
            retryAttempts: 3 // Tentativas de preenchimento
        };
        this.isRunning = false;
    }

    /**
     * Aguarda um elemento aparecer na página
     * @param {string} selector - Seletor CSS do elemento
     * @param {number} timeout - Tempo limite em ms
     * @returns {Promise<Element>}
     */
    waitForElement(selector, timeout = this.config.waitForElement) {
        return new Promise((resolve, reject) => {
            const startTime = Date.now();
            
            const checkElement = () => {
                const element = document.querySelector(selector);
                if (element) {
                    resolve(element);
                    return;
                }
                
                if (Date.now() - startTime >= timeout) {
                    reject(new Error(`Elemento ${selector} não encontrado após ${timeout}ms`));
                    return;
                }
                
                setTimeout(checkElement, 100);
            };
            
            checkElement();
        });
    }

    /**
     * Preenche um campo de input
     * @param {string} selector - Seletor do campo
     * @param {string} value - Valor a ser preenchido
     * @param {string} type - Tipo do campo (text, email, etc.)
     */
    async fillField(selector, value, type = 'text') {
        try {
            const element = await this.waitForElement(selector);
            
            // Foca no elemento
            element.focus();
            
            // Limpa o campo
            element.value = '';
            
            // Simula digitação natural
            if (type === 'typing') {
                await this.typeNaturally(element, value);
            } else {
                element.value = value;
            }
            
            // Dispara eventos necessários
            element.dispatchEvent(new Event('input', { bubbles: true }));
            element.dispatchEvent(new Event('change', { bubbles: true }));
            element.dispatchEvent(new Event('blur', { bubbles: true }));
            
            console.log(`Campo ${selector} preenchido com: ${value}`);
            
            // Delay entre preenchimentos
            await this.sleep(this.config.delay);
            
        } catch (error) {
            console.error(`Erro ao preencher campo ${selector}:`, error);
            throw error;
        }
    }

    /**
     * Simula digitação natural
     * @param {Element} element - Elemento de input
     * @param {string} text - Texto a ser digitado
     */
    async typeNaturally(element, text) {
        for (let i = 0; i < text.length; i++) {
            element.value += text[i];
            element.dispatchEvent(new Event('input', { bubbles: true }));
            await this.sleep(Math.random() * 100 + 50); // Delay aleatório entre 50-150ms
        }
    }

    /**
     * Seleciona uma opção em um select
     * @param {string} selector - Seletor do select
     * @param {string} value - Valor ou texto da opção
     */
    async selectOption(selector, value) {
        try {
            const select = await this.waitForElement(selector);
            
            // Tenta por value primeiro
            let option = select.querySelector(`option[value="${value}"]`);
            
            // Se não encontrar, tenta por texto
            if (!option) {
                const options = select.querySelectorAll('option');
                option = Array.from(options).find(opt => 
                    opt.textContent.trim().toLowerCase().includes(value.toLowerCase())
                );
            }
            
            if (option) {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                console.log(`Opção selecionada em ${selector}: ${value}`);
            } else {
                throw new Error(`Opção ${value} não encontrada em ${selector}`);
            }
            
            await this.sleep(this.config.delay);
            
        } catch (error) {
            console.error(`Erro ao selecionar opção ${selector}:`, error);
            throw error;
        }
    }

    /**
     * Clica em um elemento
     * @param {string} selector - Seletor do elemento
     */
    async clickElement(selector) {
        try {
            const element = await this.waitForElement(selector);
            
            // Scroll para o elemento se necessário
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            await this.sleep(300);
            
            element.click();
            console.log(`Elemento clicado: ${selector}`);
            
            await this.sleep(this.config.delay);
            
        } catch (error) {
            console.error(`Erro ao clicar em ${selector}:`, error);
            throw error;
        }
    }

    /**
     * Marca/desmarca checkbox ou radio
     * @param {string} selector - Seletor do elemento
     * @param {boolean} checked - Se deve estar marcado
     */
    async setCheckbox(selector, checked = true) {
        try {
            const element = await this.waitForElement(selector);
            
            if (element.checked !== checked) {
                element.click();
                console.log(`Checkbox ${selector} ${checked ? 'marcado' : 'desmarcado'}`);
            }
            
            await this.sleep(this.config.delay);
            
        } catch (error) {
            console.error(`Erro ao definir checkbox ${selector}:`, error);
            throw error;
        }
    }

    /**
     * Preenche formulário baseado em configuração
     * @param {Object} formConfig - Configuração do formulário
     */
    async fillForm(formConfig) {
        if (this.isRunning) {
            console.warn('Preenchimento já está em execução');
            return;
        }
        
        this.isRunning = true;
        
        try {
            console.log('Iniciando preenchimento automático...');
            
            for (const field of formConfig.fields) {
                try {
                    switch (field.type) {
                        case 'input':
                        case 'text':
                        case 'email':
                        case 'password':
                        case 'tel':
                        case 'number':
                            await this.fillField(field.selector, field.value, field.inputType || 'text');
                            break;
                            
                        case 'select':
                            await this.selectOption(field.selector, field.value);
                            break;
                            
                        case 'checkbox':
                        case 'radio':
                            await this.setCheckbox(field.selector, field.checked !== false);
                            break;
                            
                        case 'click':
                            await this.clickElement(field.selector);
                            break;
                            
                        case 'textarea':
                            await this.fillField(field.selector, field.value);
                            break;
                            
                        default:
                            console.warn(`Tipo de campo não suportado: ${field.type}`);
                    }
                } catch (fieldError) {
                    console.error(`Erro no campo ${field.selector}:`, fieldError);
                    if (field.required) {
                        throw fieldError;
                    }
                }
            }
            
            console.log('Preenchimento concluído com sucesso!');
            
            // Se há botão de submit configurado, clica nele
            if (formConfig.submitButton) {
                await this.sleep(1000); // Aguarda um pouco antes de submeter
                await this.clickElement(formConfig.submitButton);
            }
            
        } catch (error) {
            console.error('Erro durante preenchimento:', error);
            throw error;
        } finally {
            this.isRunning = false;
        }
    }

    /**
     * Função de sleep
     * @param {number} ms - Milissegundos para aguardar
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Monitora mudanças na página
     * @param {Function} callback - Função a ser chamada quando houver mudanças
     */
    observePageChanges(callback) {
        const observer = new MutationObserver(callback);
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true
        });
        return observer;
    }

    /**
     * Extrai dados de um formulário existente
     * @param {string} formSelector - Seletor do formulário
     * @returns {Object} Configuração extraída
     */
    extractFormConfig(formSelector = 'form') {
        const form = document.querySelector(formSelector);
        if (!form) {
            throw new Error('Formulário não encontrado');
        }
        
        const config = {
            formSelector: formSelector,
            fields: []
        };
        
        // Extrai inputs
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const field = {
                selector: this.generateSelector(input),
                type: input.tagName.toLowerCase(),
                name: input.name || input.id || '',
                placeholder: input.placeholder || '',
                required: input.required
            };
            
            if (input.type) {
                field.inputType = input.type;
            }
            
            config.fields.push(field);
        });
        
        return config;
    }

    /**
     * Gera seletor CSS para um elemento
     * @param {Element} element - Elemento DOM
     * @returns {string} Seletor CSS
     */
    generateSelector(element) {
        if (element.id) {
            return `#${element.id}`;
        }
        
        if (element.name) {
            return `[name="${element.name}"]`;
        }
        
        if (element.className) {
            const classes = element.className.split(' ').filter(c => c.trim());
            if (classes.length > 0) {
                return `.${classes.join('.')}`;
            }
        }
        
        // Fallback para seletor por tag e posição
        const siblings = Array.from(element.parentNode.children);
        const index = siblings.indexOf(element);
        return `${element.tagName.toLowerCase()}:nth-child(${index + 1})`;
    }
}

// Instância global
window.FormAutoFiller = FormAutoFiller;

// Exemplo de uso
const autoFiller = new FormAutoFiller();

// Configuração de exemplo
const exemploConfig = {
    fields: [
        {
            selector: '#nome',
            type: 'input',
            value: 'João Silva',
            required: true
        },
        {
            selector: '#email',
            type: 'email',
            value: 'joao@email.com',
            required: true
        },
        {
            selector: '#telefone',
            type: 'tel',
            value: '(11) 99999-9999'
        },
        {
            selector: '#estado',
            type: 'select',
            value: 'SP'
        },
        {
            selector: '#aceito_termos',
            type: 'checkbox',
            checked: true
        },
        {
            selector: '#observacoes',
            type: 'textarea',
            value: 'Observações automáticas'
        }
    ],
    submitButton: '#btn-enviar'
};

// Função para executar o preenchimento
window.executarPreenchimento = function(config = exemploConfig) {
    autoFiller.fillForm(config).catch(error => {
        console.error('Erro no preenchimento:', error);
        alert('Erro durante o preenchimento: ' + error.message);
    });
};

// Função para extrair configuração de formulário
window.extrairConfigFormulario = function(seletor = 'form') {
    try {
        const config = autoFiller.extractFormConfig(seletor);
        console.log('Configuração extraída:', config);
        return config;
    } catch (error) {
        console.error('Erro ao extrair configuração:', error);
        return null;
    }
};

console.log('Form Auto Filler carregado! Use executarPreenchimento() para testar.');