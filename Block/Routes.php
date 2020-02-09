<?php
declare(strict_types = 1);

namespace FireGento\WebapiMetrics\Block;

use FireGento\WebapiMetrics\Api\LoggingEntryRepositoryInterface;
use FireGento\WebapiMetrics\Api\LoggingRouteRepositoryInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Magento\Backend\Block\Dashboard\AbstractDashboard;

/**
 * Class Routes
 */
class Routes extends \Magento\Backend\Block\Dashboard\AbstractDashboard
{
    /**
     * Api URL
     */
    private const API_URL = 'https://image-charts.com/chart';
    /**
     * @var string
     */
    protected $_template = 'FireGento_WebapiMetrics::dashboard/metrics.phtml';

    /**
     * Adminhtml dashboard data
     *
     * @var \Magento\Backend\Helper\Dashboard\Data
     */
    protected $_dashboardData = null;
    /** @var LoggingEntryRepositoryInterface */
    private $loggingEntryRepository;
    /** @var LoggingRouteRepositoryInterface */
    private $loggingRouteRepository;
    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;
    /** @var FilterBuilder */
    private $filterBuilder;
    /** @var FilterGroupBuilder */
    private $filterGroupBuilder;

    /**
     * Routes constructor.
     *
     * @param Context                                                      $context
     * @param CollectionFactory                                            $collectionFactory
     * @param \FireGento\WebapiMetrics\Api\LoggingEntryRepositoryInterface $loggingEntryRepository
     * @param \FireGento\WebapiMetrics\Api\LoggingRouteRepositoryInterface $loggingRouteRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder                 $searchCriteriaBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder             $filterGroupBuilder
     * @param \Magento\Framework\Api\FilterBuilder                         $filterBuilder
     * @param array                                                        $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        LoggingEntryRepositoryInterface $loggingEntryRepository,
        LoggingRouteRepositoryInterface $loggingRouteRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        FilterBuilder $filterBuilder,
        array $data = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->loggingEntryRepository = $loggingEntryRepository;
        $this->loggingRouteRepository = $loggingRouteRepository;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        parent::__construct($context, $collectionFactory, $data);
    }

    /**
     * Get chart url
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChartUrl()
    {
        $routes = $this->loggingRouteRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        $results = [];
        foreach ($routes as $route) {
            $filter = $this->filterBuilder
                ->setField('route_id')
                ->setConditionType('eq')
                ->setValue($route->getEntityId())
                ->create();
            $filterGroup = $this->filterGroupBuilder
                ->addFilter($filter)
                ->create();
            $searchCriteria = $this->searchCriteriaBuilder
                ->setFilterGroups([$filterGroup])
                ->create();
            $entries = $this->loggingEntryRepository->getList($searchCriteria);
            $results[$route->getEntityId()] = [
                'route' => $route,
                'entries' => $entries,
                'count' => $entries->getTotalCount()
            ];
        }

        $chd = [];
        $chdl = [];

        foreach ($results as $result) {
            $chd[] = $result['count'];
            $tmp = $result['route'];
            $chdl[] = $tmp->getMethodType() . ': ' . $tmp->getRouteName();
        }

        $params = [
            'cht' => 'bhg',
            'chtt' => 'WebApi Metrics',
            'chs' => '999x500',
            'chd' => 't:' . implode('|', $chd),
            'chdl' => implode('|', $chdl),
//            'chxl' => '0:|' . implode('|', $methodTypes),
//            'chxs' => '1N**K',
//            'chxt' => 'x,y',
            'chma' => '0,0,10,10',
            'chan' => '8000,easeOutBack',
            'chco' => 'fdb45c,27c9c2,1869b7',
            'chds' => '0,120',
            'chm' => 'N,000000,0,,10|N,000000,1,,10|N,000000,2,,10',
            'chxs' => '0,000000,0,0,_',
            'chxt' => 'y'
//            'chf' => 'b0,lg,90,EA469EFF,1,03A9F47C,0.4',
        ];

        $p = [];
        foreach ($params as $name => $value) {
            $p[] = $name . '=' . urlencode($value);
        }
        return (string)self::API_URL . '?' . implode('&', $p);
    }
}
