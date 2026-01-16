<?php

namespace Tests\Unit\Services\FeeCalculator;

use App\Services\ConfigurationService;
use App\Services\FeeCalculator\FeeCalculatorContext;
use App\Services\FeeCalculator\HighAmountFeeStrategy;
use App\Services\FeeCalculator\LowAmountFeeStrategy;
use App\Services\FeeCalculator\MediumAmountFeeStrategy;
use Mockery;
use Tests\TestCase;

class FeeCalculatorTest extends TestCase
{
    protected $configurationService;
    protected $feeCalculatorContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurationService = Mockery::mock(ConfigurationService::class);
        $this->feeCalculatorContext = new FeeCalculatorContext($this->configurationService);
    }

    public function test_low_amount_strategy_calculation()
    {
        // Arrange
        $amount = 500;
        $fixedFee = 2.0;

        $this->configurationService->shouldReceive('getFloat')
            ->with('FEE_LOW_FIXED', 2.0)
            ->andReturn($fixedFee);

        $strategy = new LowAmountFeeStrategy($this->configurationService);

        // Act
        $fee = $strategy->calculate($amount);

        // Assert
        $this->assertEquals($fixedFee, $fee);
    }

    public function test_medium_amount_strategy_calculation()
    {
        // Arrange
        $amount = 5000;
        $rate = 0.005; // 0.5%
        $expectedFee = $amount * $rate; // 25

        $this->configurationService->shouldReceive('getFloat')
            ->with('FEE_MEDIUM_RATE', 0.005)
            ->andReturn($rate);

        $strategy = new MediumAmountFeeStrategy($this->configurationService);

        // Act
        $fee = $strategy->calculate($amount);

        // Assert
        $this->assertEquals($expectedFee, $fee);
    }

    public function test_high_amount_strategy_calculation()
    {
        // Arrange
        $amount = 20000;
        $baseFee = 20.0; // from config
        $thresholdLow = 1000.0;
        $rate = 0.003; // 0.3%
        
        // Logic: baseFee + (amount - thresholdLow) * rate
        // 20 + (20000 - 1000) * 0.003 = 20 + 19000 * 0.003 = 20 + 57 = 77
        $expectedFee = 77.0;

        $this->configurationService->shouldReceive('getFloat')
            ->with('FEE_HIGH_BASE_FEE', 2.0)
            ->andReturn($baseFee);
        
        $this->configurationService->shouldReceive('getFloat')
            ->with('FEE_THRESHOLD_LOW', 1000.0)
            ->andReturn($thresholdLow);

        $this->configurationService->shouldReceive('getFloat')
            ->with('FEE_HIGH_RATE', 0.003)
            ->andReturn($rate);

        $strategy = new HighAmountFeeStrategy($this->configurationService);

        // Act
        $fee = $strategy->calculate($amount);

        // Assert
        $this->assertEquals($expectedFee, $fee, "High amount fee calculation is incorrect.");
    }

    public function test_context_selects_correct_strategy_based_on_thresholds()
    {
        // Config Mocking
        $this->configurationService->shouldReceive('getFloat')->with('FEE_THRESHOLD_LOW', 1000.0)->andReturn(1000.0);
        $this->configurationService->shouldReceive('getFloat')->with('FEE_THRESHOLD_MEDIUM', 10000.0)->andReturn(10000.0);
        
        // For Strategy Instantiation inside Context
        $this->configurationService->shouldReceive('getFloat')->with('FEE_LOW_FIXED', 2.0)->andReturn(2.0);
        $this->configurationService->shouldReceive('getFloat')->with('FEE_MEDIUM_RATE', 0.005)->andReturn(0.005);
        $this->configurationService->shouldReceive('getFloat')->with('FEE_HIGH_BASE_FEE', 2.0)->andReturn(2.0);
        $this->configurationService->shouldReceive('getFloat')->with('FEE_HIGH_RATE', 0.003)->andReturn(0.003);

        // 1. Low Amount (< 1000)
        $feeLow = $this->feeCalculatorContext->calculateFee(500);
        $this->assertEquals(2.0, $feeLow, "Should use Low Strategy");

        // 2. Medium Amount (1000 < amount <= 10000)
        // 5000 * 0.005 = 25
        $feeMedium = $this->feeCalculatorContext->calculateFee(5000);
        $this->assertEquals(25.0, $feeMedium, "Should use Medium Strategy");

        // 3. High Amount (> 10000)
        // 15000. 
        // Logic: Base(2) + (15000 - 1000) * 0.003 = 2 + 14000 * 0.003 = 2 + 42 = 44
        $feeHigh = $this->feeCalculatorContext->calculateFee(15000);
        $this->assertEquals(44.0, $feeHigh, "Should use High Strategy");
    }
}
