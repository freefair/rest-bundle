<?php

namespace freefair\RestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rest');

        $rootNode
            ->children()
                ->booleanNode("debug")->defaultFalse()->end()
                ->arrayNode("formatters")
                    ->fixXmlConfig("formatter")
                    ->prototype("array")
                        ->children()
                            ->scalarNode("id")->end()
                            ->scalarNode("type")->end()
                            ->booleanNode("default")->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode("authentication")
                    ->children()
                        ->booleanNode("enabled")->defaultFalse()->end()
                        ->enumNode("oauth_type")->values(array("static", "own"))->defaultValue("own")->end()
                        ->arrayNode("oauth")
                            ->children()
                                ->integerNode("code_lifetime")->defaultValue(600)->end() // one month
                                ->integerNode("token_lifetime")->defaultValue(2592000)->end() // one month
                                ->scalarNode("grant_url")->defaultValue("/oauth/grant")->end()
                                ->scalarNode("token_url")->defaultValue("/oauth/token")->end()
                                ->scalarNode("grant_controller")->defaultNull()->end()
                                ->scalarNode("token_controller")->defaultValue("freefair:RestBundle:Controller:OAuthController:tokenAction")->end()
                                ->arrayNode("persistence")
                                    ->children()
                                        ->scalarNode("auth_code_entity")->end()
                                        ->scalarNode("auth_token_entity")->end()
                                        ->scalarNode("consumer_entity")->end()
                                    ->end()
                                ->end()
                                ->arrayNode("static_tokens")
                                    ->fixXmlConfig("token")
                                    ->prototype("scalar")->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
