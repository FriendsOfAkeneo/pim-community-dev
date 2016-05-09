<?php

namespace spec\Pim\Bundle\AnalyticsBundle\DataCollector;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ServerBag;

class AdvancedVersionDataCollectorSpec extends ObjectBehavior
{
    function let(RequestStack $requestStack)
    {
        $this->beConstructedWith($requestStack);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Bundle\AnalyticsBundle\DataCollector\AdvancedVersionDataCollector');
        $this->shouldHaveType('Akeneo\Component\Analytics\DataCollectorInterface');
    }

    function it_provides_server_and_sql_versions_when_pim_uses_orm(
        $requestStack,
        Request $request,
        ServerBag $serverBag
    ) {
        $requestStack->getCurrentRequest()->willReturn($request);

        $request->server = $serverBag;
        $serverBag->get('SERVER_SOFTWARE')->willReturn('Apache/2.4.12 (Debian)');

        $this->collect()->shouldHaveCount(2);
        $this->collect()->shouldHaveKeyWithValue('server_version', 'Apache/2.4.12 (Debian)');
    }

    function it_does_not_provides_server_version_of_pim_host_if_request_is_null(
        $requestStack,
        ServerBag $serverBag
    ) {
        $requestStack->getCurrentRequest()->willReturn(null);

        $serverBag->get(Argument::type('string'))->shouldNotBeCalled();

        $this->collect()->shouldHaveCount(1);

    }
}
