<?php
if (!defined('_PS_VERSION_'))
    exit;

class pwimagealt extends Module
{
    public function __construct()
    {
        $this->name = 'pwimagealt';
        $this->tab = 'other';
        $this->version = '0.2.3';
        $this->author = 'PrestaWeb.ru';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l("Alt, Title для картинок");
        $this->description = $this->l("Alt, Title для картинок");
        
        $this->ps_versions_compliancy = array('min' => '1.5.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook(array(
				'DisplayOverrideTemplate',
			))
        ) return false;
        Configuration::deleteByName('PW_IMAGEALT_CACHE_CATEGORY'); //чтобы при переустановке заново все генерировалось
        Configuration::deleteByName('PW_IMAGEALT_CACHE_PRODUCT');
        return true;
    }
    
    public function getContent()
    {
        $this->postProcess();
        return $this->renderForm();
    }
    
	public function hookDisplayOverrideTemplate($params)
    {
        if ($this->context->controller instanceof ProductController && (bool)Configuration::get('PW_IMAGEALT_PRODUCT')) {
            $this->generateProductAlt('product.tpl');
            if (file_exists($this->getPath('product.tpl'))) {
                return $this->getPath('product.tpl');
            }
        } elseif($this->context->controller instanceof CategoryController && (bool)Configuration::get('PW_IMAGEALT_CATEGORY')) {
            $this->generateCategoryAlt('category.tpl');
            if (file_exists($this->getPath('category.tpl'))) {
                return $this->getPath('category.tpl');
            }
        }
        return false;
	}
    
    //делает замену в шаблоне товаров
    private function generateProductAlt($tpl)
    {
        $original = _PS_THEME_DIR_.$tpl;
        $new      = $this->getPath($tpl);
        $last_modified = filemtime($original);
        $cached = (int)Configuration::get('PW_IMAGEALT_CACHE_PRODUCT');
        if ($last_modified != $cached || !file_exists($new)) {
            $content = file_get_contents($original);
            $new_str = $this->getProductString();
            preg_match_all('/<img .*?id="bigpic".*?\/>/i', $content, $matches);
            $matches = $matches[0];
            foreach ($matches as $match) {
                $content = preg_replace('/ alt="(.[^=]*)?" /i', ' ', $content); //удаляем alt
                $content = preg_replace('/ title="(.[^=]*)?" /i', ' ', $content); //удаляем title
                $content = str_replace('<img', '<img '.$new_str, $content);
            }
            $content = str_replace('{include file="./', '{include file="$tpl_dir./', $content); //типа фикс
            if (file_put_contents($new, $content) !== false) {
                Configuration::updateValue('PW_IMAGEALT_CACHE_PRODUCT', $last_modified);
            }
        }
    }
    
    //Делает замену в шаблоне категорий
    private function generateCategoryAlt($tpl)
    {
        $original = _PS_THEME_DIR_.$tpl;
        $new      = $this->getPath($tpl);
        $last_modified = filemtime($original);
        $cached = (int)Configuration::get('PW_IMAGEALT_CACHE_CATEGORY');
        if ($last_modified != $cached || !file_exists($new)) {
            $content = file_get_contents($original);
            $new_str = $this->getCategoryString();
            preg_match_all('/<img .*?src="\{\$link-\>getCatImageLink\(\$category-\>link_rewrite.*\}".*?\/>/i', $content, $matches);
            $matches = $matches[0];
            foreach ($matches as $match) {
                $content = preg_replace('/ alt="(.[^=]*)?" /i', ' ', $content); //удаляем alt
                $content = preg_replace('/ title="(.[^=]*)?" /i', ' ', $content); //удаляем title
                $content = str_replace('<img', '<img '.$new_str, $content);
            }
            $content = str_replace('{include file="./', '{include file="$tpl_dir./', $content); //типа фикс
            if (file_put_contents($new, $content) !== false) {
                Configuration::updateValue('PW_IMAGEALT_CACHE_CATEGORY', $last_modified);
            }
        }
    }
    
    //что будет вставляться в картинку на странице товров
    private function getProductString()
    {
        return 'alt="{$product->name}" title="{$product->name}"';
    }
    
    //что будет вставляться в картинку на странице категорий
    private function getCategoryString()
    {
        return 'alt="{$category->name}" title="{$category->name}"';
    }
    
    //возвращает путь к новому шаблону
    private function getPath($tpl)
    {
        $path = _PS_MODULE_DIR_.$this->name.'/views/generated/';
        return $path.$tpl;
    }
    
    private function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Настройки'),
                    'icon' => 'icon-cogs'
                ),
                'description' => $this->l('Внимание! Модуль находится в разработке и стабильная работа не гарантируется. После установки обязательно проверьте открываются ли страницы товаров и категорий на сайте.'),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Включить для категорий'),
                        'name' => 'PW_IMAGEALT_CATEGORY',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PW_IMAGEALT_CATEGORY_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'PW_IMAGEALT_CATEGORY_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Включить для товаров'),
                        'name' => 'PW_IMAGEALT_PRODUCT',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PW_IMAGEALT_PRODUCT_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'PW_IMAGEALT_PRODUCT_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Сохранить'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPWIMAGEALT';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    private function getConfigFieldsValues()
    {
        return array(
            'PW_IMAGEALT_CATEGORY' => Configuration::get('PW_IMAGEALT_CATEGORY'),
            'PW_IMAGEALT_PRODUCT' => Configuration::get('PW_IMAGEALT_PRODUCT'),
        );
    }
    
    private function postProcess()
    {
        if (Tools::isSubmit('submitPWIMAGEALT'))
        {
            Configuration::updateValue('PW_IMAGEALT_CATEGORY', Tools::getValue('PW_IMAGEALT_CATEGORY'));
            Configuration::updateValue('PW_IMAGEALT_PRODUCT', Tools::getValue('PW_IMAGEALT_PRODUCT'));
        }
    }
}


