<?php

/**
 * dc_multilingual Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2011-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-dc_multilingual
 */

namespace Terminal42\DcMultilingualBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Terminal42\DcMultilingualBundle\QueryBuilder\MultilingualQueryBuilderFactoryInterface;

class Multilingual extends \Model
{
    /**
     * Build a query based on the given options.
     * The method returns a QueryBuilder instance so you can easily modify
     * the query in your child class. We can just return the instance as the
     * QueryBuilder implements the __toString() method so we don't have to call
     * ->getSql() manually.
     *
     * @param array $options The options array
     *
     * @return QueryBuilder
     */
    protected static function buildFindQuery(array $options)
    {
        $mlqb = static::getMultilingualQueryBuilder();

        static::applyOptionsToQueryBuilder($mlqb->getQueryBuilder(), $options);

        // Use the current language if none provided
        if (!isset($options['language'])) {
            $options['language'] = str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
        }

        // Consider the fallback language
        $fallbackLang = static::getFallbackLanguage();
        if (null !== $fallbackLang && $fallbackLang === $options['language']) {
            $options['language'] = '';
        }

        $mlqb->buildQueryBuilderForFind($options['language']);

        return $mlqb->getQueryBuilder();
    }

    /**
     * Build a query based on the given options to count the number of records.
     * The method returns a QueryBuilder instance so you can easily modify
     * the query in your child class. We can just return the instance as the
     * QueryBuilder implements the __toString() method so we don't have to call
     * ->getSql() manually.
     *
     * @param array $options The options array
     *
     * @return string The query string
     */
    protected static function buildCountQuery(array $options)
    {
        $mlqb = static::getMultilingualQueryBuilder();

        static::applyOptionsToQueryBuilder($mlqb->getQueryBuilder(), $options);

        $mlqb->buildQueryBuilderForCount();

        return $mlqb->getQueryBuilder();
    }

    /**
     * Apply the model options to the query builder.
     *
     * @param QueryBuilder $qb
     * @param array        $options
     */
    protected static function applyOptionsToQueryBuilder(QueryBuilder $qb, array $options)
    {
        // Columns
        if (null !== $options['column']) {
            if (is_array($options['column'])) {
                foreach ($options['column'] as $column) {
                    $qb->andWhere($column);
                }
            } else {
                $qb->andWhere($options['table'] . '.' . $options['column'] . '=?');
            }
        }

        // Group by
        if (null !== $options['group']) {
            $qb->groupBy($options['group']);
        }

        // Having
        if ($options['having'] !== null) {
            $qb->having($options['having']);
        }

        // Order by
        if (null !== $options['order']) {
            $qb->add('orderBy', $options['order']);
        }
    }

    /**
     * Get the MultilingualQueryBuilder.
     *
     * @return \Terminal42\DcMultilingualBundle\QueryBuilder\MultilingualQueryBuilderInterface
     */
    protected static function getMultilingualQueryBuilder()
    {
        /** @var MultilingualQueryBuilderFactoryInterface $factory */
        $factory = \System::getContainer()->get('terminal42.dc_multilingual.querybuilder_factory');

        return $factory->build(
            static::getTable(),
            static::getPidColumn(),
            static::getLangColumn(),
            static::getRegularFields(),
            static::getTranslatableFields()
        );
    }

    /**
     * Get the regular fields
     *
     * @return array
     */
    protected static function getRegularFields()
    {
        $extractor = \DcaExtractor::getInstance(static::getTable());

        return array_keys($extractor->getFields());
    }

    /**
     * Get the fields that are translatable.
     *
     * @return array
     */
    protected static function getTranslatableFields()
    {
        $fields = [];

        foreach ($GLOBALS['TL_DCA'][static::getTable()]['fields'] as $field => $data) {
            if (!isset($data['eval']['translatableFor'])) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Get the PID column.
     *
     * @return string
     */
    protected static function getPidColumn()
    {
        if ($GLOBALS['TL_DCA'][static::getTable()]['config']['langPid']) {

            return $GLOBALS['TL_DCA'][static::getTable()]['config']['langPid'];
        }

        return 'langPid';
    }

    /**
     * Get the language column.
     *
     * @return string
     */
    public static function getLangColumn()
    {
        if ($GLOBALS['TL_DCA'][static::getTable()]['config']['langColumn']) {

            return $GLOBALS['TL_DCA'][static::getTable()]['config']['langColumn'];
        }

        return 'language';
    }

    /**
     * Get the fallback language if available.
     *
     * @return string|null
     */
    public static function getFallbackLanguage()
    {
        if ($GLOBALS['TL_DCA'][static::getTable()]['config']['fallbackLang']) {

            return $GLOBALS['TL_DCA'][static::getTable()]['config']['fallbackLang'];
        }

        return null;
    }
}