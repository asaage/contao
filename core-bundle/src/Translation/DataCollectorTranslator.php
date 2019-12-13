<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Translation;

use Symfony\Component\Translation\DataCollectorTranslator as SymfonyDataCollectorTranslator;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class DataCollectorTranslator extends SymfonyDataCollectorTranslator implements ResetInterface
{
    /**
     * @var SymfonyDataCollectorTranslator
     */
    private $translator;

    private $messages = [];

    /**
     * @param SymfonyDataCollectorTranslator $translator
     */
    public function __construct($translator)
    {
        parent::__construct($translator);

        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * Gets the translation from Contao’s $GLOBALS['TL_LANG'] array if the message
     * domain starts with "contao_". The locale parameter is ignored in this case.
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        $translated = $this->translator->trans($id, $parameters, $domain, $locale);

        // Forward to the default translator
        if (null === $domain || 0 !== strncmp($domain, 'contao_', 7)) {
            return $translated;
        }

        $this->collectMessage($this->getLocale(), (string) $domain, $id, $translated, $parameters);

        return $translated;
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null): string
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale): void
    {
        $this->translator->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function getCatalogue($locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Merges the collected messages from the decorated translator.
     */
    public function getCollectedMessages(): array
    {
        if (method_exists($this->translator, 'getCollectedMessages')) {
            return array_merge($this->translator->getCollectedMessages(), $this->messages);
        }

        return $this->messages;
    }

    public function reset(): void
    {
        $this->messages = [];
    }

    private function collectMessage(string $locale, string $domain, string $id, string $translation, array $parameters = []): void
    {
        if ($id === $translation) {
            $state = SymfonyDataCollectorTranslator::MESSAGE_MISSING;
        } else {
            $state = SymfonyDataCollectorTranslator::MESSAGE_DEFINED;
        }

        $this->messages[] = [
            'locale' => $locale,
            'domain' => $domain,
            'id' => $id,
            'translation' => $translation,
            'parameters' => $parameters,
            'state' => $state,
            'transChoiceNumber' => isset($parameters['%count%']) && is_numeric($parameters['%count%']) ? $parameters['%count%'] : null,
        ];
    }
}
