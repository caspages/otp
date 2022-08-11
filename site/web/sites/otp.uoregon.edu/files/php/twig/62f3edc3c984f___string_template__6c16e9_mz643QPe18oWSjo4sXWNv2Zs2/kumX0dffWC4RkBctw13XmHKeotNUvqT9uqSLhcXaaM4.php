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

/* __string_template__6c16e9f6d86a359d6a8672591e5b07cf603c979ab4a31a09581aeb63bb7e9c56 */
class __TwigTemplate_13c07599c372947fb79e138b1699445808486df9cd475f975d34edb041c53803 extends \Twig\Template
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
        // line 1
        echo "<div class=\"quick-fact-tile\">
  <h2>Known Years of Operation</h2>
  ";
        // line 3
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, twig_date_format_filter($this->env, $this->sandbox->ensureToStringAllowed(($context["field_header_date__value"] ?? null), 3, $this->source), "M j, Y"), "html", null, true);
        echo " - ";
        if (twig_test_empty(($context["field_header_date__end_value"] ?? null))) {
            echo " Present ";
        } else {
            echo " ";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, twig_date_format_filter($this->env, $this->sandbox->ensureToStringAllowed(($context["field_header_date__end_value"] ?? null), 3, $this->source), "M j, Y"), "html", null, true);
            echo " ";
        }
        // line 4
        echo "</div>";
    }

    public function getTemplateName()
    {
        return "__string_template__6c16e9f6d86a359d6a8672591e5b07cf603c979ab4a31a09581aeb63bb7e9c56";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  53 => 4,  43 => 3,  39 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "__string_template__6c16e9f6d86a359d6a8672591e5b07cf603c979ab4a31a09581aeb63bb7e9c56", "");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 3);
        static $filters = array("escape" => 3, "date" => 3);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape', 'date'],
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
