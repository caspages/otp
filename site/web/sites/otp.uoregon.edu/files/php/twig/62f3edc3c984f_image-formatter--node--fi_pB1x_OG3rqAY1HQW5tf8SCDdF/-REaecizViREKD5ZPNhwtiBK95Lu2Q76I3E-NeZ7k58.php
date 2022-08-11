<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* themes/corporate_lite/templates/image-formatter--node--field-image.html.twig */
class __TwigTemplate_b47fb4f4c4b3fc1dde87d09e2b107c0333170e455f8be560e63725e489f7a2b3 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 15
        if (($context["url"] ?? null)) {
            // line 16
            echo "  <div class=\"overlay-container\">
    <span class=\"overlay\">
      <span class=\"overlay-inner\">
        <span class=\"overlay-icon overlay-icon--button\">
          <i class=\"fa fa-link\"></i>
        </span>
      </span>
      <a class=\"overlay-target-link image-popup\" href=\"";
            // line 23
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["url"] ?? null), 23, $this->source), "html", null, true);
            echo "\"></a>
    </span>
    ";
            // line 25
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["image"] ?? null), 25, $this->source), "html", null, true);
            echo "
  </div>
";
        } else {
            // line 28
            echo "  ";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["image"] ?? null), 28, $this->source), "html", null, true);
            echo "
";
        }
    }

    public function getTemplateName()
    {
        return "themes/corporate_lite/templates/image-formatter--node--field-image.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  61 => 28,  55 => 25,  50 => 23,  41 => 16,  39 => 15,);
    }

    public function getSourceContext()
    {
        return new Source("", "themes/corporate_lite/templates/image-formatter--node--field-image.html.twig", "/var/www/sites/otp-github-d9.4.5/web/themes/corporate_lite/templates/image-formatter--node--field-image.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 15);
        static $filters = array("escape" => 23);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
