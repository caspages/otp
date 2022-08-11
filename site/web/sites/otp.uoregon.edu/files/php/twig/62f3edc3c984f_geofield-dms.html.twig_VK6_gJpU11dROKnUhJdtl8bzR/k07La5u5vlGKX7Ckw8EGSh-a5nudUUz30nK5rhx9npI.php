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

/* modules/contrib/geofield/templates/geofield-dms.html.twig */
class __TwigTemplate_c5dc3ca10211b6760d80008954cdbcc36625bd9fb0b5ecfe0a3fa1a1bd3a776d extends \Twig\Template
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
        // line 16
        echo "
";
        // line 17
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["components"] ?? null));
        foreach ($context['_seq'] as $context["item"] => $context["component"]) {
            // line 18
            echo "  <span class=\"dms dms-";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($context["item"], 18, $this->source), "html", null, true);
            echo "\">
    ";
            // line 19
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed((($__internal_compile_0 = $context["component"]) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["degrees"] ?? null) : null), 19, $this->source), "html", null, true);
            echo "°
    ";
            // line 20
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed((($__internal_compile_1 = $context["component"]) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["minutes"] ?? null) : null), 20, $this->source), "html", null, true);
            echo "'
    ";
            // line 21
            if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["component"], "seconds", [], "any", false, false, true, 21))) {
                // line 22
                echo "      ";
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed((($__internal_compile_2 = $context["component"]) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["seconds"] ?? null) : null), 22, $this->source), "html", null, true);
                echo "\"
    ";
            }
            // line 24
            echo "    ";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed((($__internal_compile_3 = $context["component"]) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["orientation"] ?? null) : null), 24, $this->source), "html", null, true);
            echo "
  </span>
  ";
            // line 26
            if (($context["item"] == "lat")) {
                // line 27
                echo "    ,
  ";
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['item'], $context['component'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    public function getTemplateName()
    {
        return "modules/contrib/geofield/templates/geofield-dms.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  75 => 27,  73 => 26,  67 => 24,  61 => 22,  59 => 21,  55 => 20,  51 => 19,  46 => 18,  42 => 17,  39 => 16,);
    }

    public function getSourceContext()
    {
        return new Source("", "modules/contrib/geofield/templates/geofield-dms.html.twig", "/var/www/sites/otp-github-d9.4.5/web/modules/contrib/geofield/templates/geofield-dms.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("for" => 17, "if" => 21);
        static $filters = array("escape" => 18);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['for', 'if'],
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
