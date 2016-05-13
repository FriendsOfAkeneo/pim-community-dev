<?php

namespace Pim\Component\Catalog\Updater;

use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Doctrine\Common\Util\ClassUtils;
use Pim\Component\Catalog\Model\AttributeGroupInterface;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Repository\AttributeGroupRepositoryInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Updates an attribute.
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeUpdater implements ObjectUpdaterInterface
{
    /** @var AttributeGroupRepositoryInterface */
    protected $attrGroupRepo;

    /** @var PropertyAccessor */
    protected $accessor;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /**
     * @param AttributeGroupRepositoryInterface $attrGroupRepo
     * @param LocaleRepositoryInterface         $localeRepository
     */
    public function __construct(
        AttributeGroupRepositoryInterface $attrGroupRepo,
        LocaleRepositoryInterface $localeRepository
    ) {
        $this->attrGroupRepo     = $attrGroupRepo;
        $this->localeRepository  = $localeRepository;
        $this->accessor          = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function update($attribute, array $data, array $options = [])
    {
        if (!$attribute instanceof AttributeInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expects a "Pim\Component\Catalog\Model\AttributeInterface", "%s" provided.',
                    ClassUtils::getClass($attribute)
                )
            );
        }

        foreach ($data as $field => $value) {
            $this->setData($attribute, $field, $value);
        }

        return $this;
    }

    /**
     * @param AttributeInterface $attribute
     * @param string             $field
     * @param mixed              $data
     *
     * @throws \InvalidArgumentException
     */
    protected function setData(AttributeInterface $attribute, $field, $data)
    {
        switch ($field) {
            case 'labels':
                $this->setLabels($attribute, $data);
                break;
            case 'group':
                $this->setGroup($attribute, $data);
                break;
            case 'available_locales':
                $this->setAvailableLocales($attribute, $data);
                break;
            case 'date_min':
                $this->validateDateFormat($data);
                $attribute->setDateMin(new \DateTime($data));
                break;
            case 'date_max':
                $this->validateDateFormat($data);
                $attribute->setDateMax(new \DateTime($data));
                break;
            default:
                $this->accessor->setValue($attribute, $field, $data);
        }
    }

    /**
     * @param string $code
     *
     * @return AttributeGroupInterface|null
     */
    protected function findAttributeGroup($code)
    {
        $attributeGroup = $this->attrGroupRepo->findOneByIdentifier($code);

        return $attributeGroup;
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $data
     */
    protected function setLabels(AttributeInterface $attribute, array $data)
    {
        foreach ($data as $localeCode => $label) {
            $attribute->setLocale($localeCode);
            $translation = $attribute->getTranslation();
            $translation->setLabel($label);
        }
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $data
     */
    protected function setAvailableLocales(AttributeInterface $attribute, array $data)
    {
        $localeSpecificCodes = $attribute->getLocaleSpecificCodes();
        foreach ($data as $localeCode) {
            if (!in_array($localeCode, $localeSpecificCodes)) {
                $locale = $this->localeRepository->findOneByIdentifier($localeCode);
                $attribute->addAvailableLocale($locale);
            }
        }
    }

    /**
     * @param AttributeInterface $attribute
     * @param string             $data
     *
     * @throws \InvalidArgumentException
     */
    protected function setGroup(AttributeInterface $attribute, $data)
    {
        $attributeGroup = $this->findAttributeGroup($data);
        if (null !== $attributeGroup) {
            $attribute->setGroup($attributeGroup);
        } else {
            throw new \InvalidArgumentException(sprintf('AttributeGroup "%s" does not exist', $data));
        }
    }

    /**
     * @param string $data
     *
     * @throws \InvalidArgumentException
     */
    protected function validateDateFormat($data)
    {
        if (!preg_match('/(\d{4})-(\d{2})-(\d{2})/', $data, $dateValues)) {
            throw new \InvalidArgumentException(
                sprintf('Attribute expects a string with the format "yyyy-mm-dd" as data, "%s" given', $data)
            );
        }

        if (!checkdate($dateValues[2], $dateValues[3], $dateValues[1])) {
            throw new \InvalidArgumentException(
                sprintf('Invalid date, "%s" given', $data)
            );
        }
    }
}
