<?php

namespace spec\Pim\Bundle\DataGridBundle\Updater;

use Akeneo\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Pim\Bundle\DataGridBundle\Entity\DatagridView;
use Pim\Bundle\DataGridBundle\Updater\DatagridViewUpdater;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\UserBundle\Entity\UserInterface;

class DatagridViewUpdaterSpec extends ObjectBehavior
{
    function let(IdentifiableObjectRepositoryInterface $userRepository)
    {
        $this->beConstructedWith($userRepository);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(DatagridViewUpdater::class);
    }

    function it_is_a_object_updater()
    {
        $this->shouldImplement(ObjectUpdaterInterface::class);
    }

    function it_throws_an_exception_if_the_given_object_is_not_a_datagrid(UserInterface $user)
    {
        $this->shouldThrow('\InvalidArgumentException')->during('update', [$user, []]);
    }

    function it_updates_the_data_grid_property($userRepository, DatagridView $datagridView, UserInterface $user)
    {
        $userRepository->findOneByIdentifier('julia')->willreturn($user);

        $datagridView->setLabel('My view')->shouldBeCalled();
        $datagridView->setOwner($user)->shouldBeCalled();
        $datagridView->setType(DatagridView::TYPE_PUBLIC)->shouldBeCalled();
        $datagridView->setDatagridAlias('product-grid')->shouldBeCalled();
        $datagridView->setColumns(['name', 'price'])->shouldBeCalled();
        $datagridView->setFilters('my filter as string')->shouldBeCalled();

        $this->update($datagridView, [
            'owner' => 'julia',
            'type' => DatagridView::TYPE_PUBLIC,
            'datagrid_alias' => 'product-grid',
            'label' => 'My view',
            'columns' => 'name, price',
            'filters' => 'my filter as string',
        ])->shouldReturn($this);
    }
}
