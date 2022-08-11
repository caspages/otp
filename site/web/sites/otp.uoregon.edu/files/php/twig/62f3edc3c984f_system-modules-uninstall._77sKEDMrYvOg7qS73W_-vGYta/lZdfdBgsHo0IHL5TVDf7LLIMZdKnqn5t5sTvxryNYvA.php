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

/* core/modules/system/templates/system-modules-uninstall.html.twig */
class __TwigTemplate_aad39f5fa7b2c625764ad7d95df06b02b6cf750b2be378f646c1c9cbc02f5520 extends \Twig\Template
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
        // line 24
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["form"] ?? null), "filters", [], "any", false, false, true, 24), 24, $this->source), "html", null, true);
        echo "

<table class=\"responsive-enabled\">
  <thead>
    <tr>
      <th>";
        // line 29
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Uninstall"));
        echo "</th>
      <th>";
        // line 30
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Name"));
        echo "</th>
      <th>";
        // line 31
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Description"));
        echo "</th>
    </tr>
  </thead>
  <tbody>
    ";
        // line 35
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["modules"] ?? null));
        $context['_iterated'] = false;
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["module"]) {
            // line 36
            echo "      ";
            $context["zebra"] = twig_cycle([0 => "odd", 1 => "even"], $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["loop"], "index0", [], "any", false, false, true, 36), 36, $this->source));
            // line 37
            echo "<tr";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["module"], "attributes", [], "any", false, false, true, 37), "addClass", [0 => ($context["zebra"] ?? null)], "method", false, false, true, 37), 37, $this->source), "html", null, true);
            echo ">
        <td align=\"center\">";
            // line 39
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["module"], "checkbox", [], "any", false, false, true, 39), 39, $this->source), "html", null, true);
            // line 40
            echo "</td>
        <td>
          <label for=\"";
            // line 42
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["module"], "checkbox_id", [], "any", false, false, true, 42), 42, $this->source), "html", null, true);
            echo "\" class=\"module-name table-filter-text-source\">";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["module"], "name", [], "any", false, false, true, 42), 42, $this->source), "html", null, true);
            echo "</label>
        </td>
        <td class=\"description\">
          <span class=\"text module-description\">";
            // line 45
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["module"], "description", [], "any", false, false, true, 45), 45, $this->source), "html", null, true);
            echo "</span>
          ";
            // line 46
            if ((twig_get_attribute($this->env, $this->source, $context["module"], "reasons_count", [], "any", false, false, true, 46) > 0)) {
                // line 47
                echo "            <div class=\"admin-requirements\">";
                // line 48
                echo \Drupal::translation()->formatPlural(abs(twig_get_attribute($this->env, $this->source,                 // line 50
$context["module"], "reasons_count", [], "any", false, false, true, 50)), "The following reason prevents @module.module_name from being uninstalled:", "The following reasons prevent @module.module_name from being uninstalled:", array("@module.module_name" => twig_get_attribute($this->env, $this->source,                 // line 49
$context["module"], "module_name", [], "any", false, false, true, 49), "@module.module_name" => twig_get_attribute($this->env, $this->source,                 // line 51
$context["module"], "module_name", [], "any", false, false, true, 51), ));
                // line 53
                echo "              <div class=\"item-list\">
                <ul>";
                // line 55
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["module"], "validation_reasons", [], "any", false, false, true, 55));
                foreach ($context['_seq'] as $context["_key"] => $context["reason"]) {
                    // line 56
                    echo "<li>";
                    echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($context["reason"], 56, $this->source), "html", null, true);
                    echo "</li>";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['reason'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 58
                if (twig_get_attribute($this->env, $this->source, $context["module"], "required_by", [], "any", false, false, true, 58)) {
                    // line 59
                    echo "<li>";
                    echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Required by: @module-list", ["@module-list" => $this->extensions['Drupal\Core\Template\TwigExtension']->safeJoin($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["module"], "required_by", [], "any", false, false, true, 59), 59, $this->source), ", ")]));
                    echo "</li>";
                }
                // line 61
                echo "</ul>
              </div>
            </div>
          ";
            }
            // line 65
            echo "        </td>
      </tr>
    ";
            $context['_iterated'] = true;
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        if (!$context['_iterated']) {
            // line 68
            echo "      <tr class=\"odd\">
        <td colspan=\"3\" class=\"empty message\">";
            // line 69
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("No modules are available to uninstall."));
            echo "</td>
      </tr>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['module'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 72
        echo "  </tbody>
</table>

";
        // line 75
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->withoutFilter($this->sandbox->ensureToStringAllowed(($context["form"] ?? null), 75, $this->source), "filters", "modules", "uninstall"), "html", null, true);
        echo "
";
    }

    public function getTemplateName()
    {
        return "core/modules/system/templates/system-modules-uninstall.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  175 => 75,  170 => 72,  161 => 69,  158 => 68,  143 => 65,  137 => 61,  132 => 59,  130 => 58,  122 => 56,  118 => 55,  115 => 53,  113 => 51,  112 => 49,  111 => 50,  110 => 48,  108 => 47,  106 => 46,  102 => 45,  94 => 42,  90 => 40,  88 => 39,  83 => 37,  80 => 36,  62 => 35,  55 => 31,  51 => 30,  47 => 29,  39 => 24,);
    }

    public function getSourceContext()
    {
        return new Source("", "core/modules/system/templates/system-modules-uninstall.html.twig", "/var/www/sites/otp-github-d9.4.5/web/core/modules/system/templates/system-modules-uninstall.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("for" => 35, "set" => 36, "if" => 46, "trans" => 48);
        static $filters = array("escape" => 24, "t" => 29, "safe_join" => 59, "without" => 75);
        static $functions = array("cycle" => 36);

        try {
            $this->sandbox->checkSecurity(
                ['for', 'set', 'if', 'trans'],
                ['escape', 't', 'safe_join', 'without'],
                ['cycle']
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
