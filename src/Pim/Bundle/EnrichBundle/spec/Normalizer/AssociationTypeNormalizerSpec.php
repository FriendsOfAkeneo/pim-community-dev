<?php

namespace spec\Pim\Bundle\EnrichBundle\Normalizer;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\VersioningBundle\Manager\VersionManager;
use Pim\Component\Catalog\Model\AssociationTypeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AssociationTypeNormalizerSpec extends ObjectBehavior
{
    public function let(
        NormalizerInterface $normalizer,
        VersionManager $versionManager,
        NormalizerInterface $versionNormalizer
    ) {
        $this->beConstructedWith(
            $normalizer,
            $versionManager,
            $versionNormalizer
        );
    }

    public function it_adds_the_attribute_id_to_the_normalized_association_type($normalizer, AssociationTypeInterface $variant)
    {
        $normalizer->normalize($variant, 'standard', [])->willReturn(['code' => 'variant']);
        $variant->getId()->willReturn(12);

        $this->normalize($variant, 'internal_api', [])
            ->shouldReturn(
                [
                    'code' => 'variant',
                    'id' => 12,
                    'meta' => [
                        'id' => 12,
                        'form' => "pim-association-type-edit-form",
                        'model_type' => "association_type",
                        'created' => null,
                        'updated' => null,
                    ]
                ]
            );
    }

    public function it_supports_association_types_and_internal_api(AssociationTypeInterface $variant)
    {
        $this->supportsNormalization($variant, 'internal_api')->shouldReturn(true);
        $this->supportsNormalization($variant, 'json')->shouldReturn(false);
        $this->supportsNormalization(new \StdClass(), 'internal_api')->shouldReturn(false);
    }
}
