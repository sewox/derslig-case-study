<?php

namespace Tests\Unit\Services\Configuration;

use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConfigurationService();
    }

    public function test_it_retrieves_value_from_database_if_not_in_cache()
    {
        // Arrange
        Configuration::create([
            'key' => 'TEST_KEY',
            'value' => 'test_value',
            'description' => 'Test Description'
        ]);

        // Act
        $value = $this->service->get('TEST_KEY');

        // Assert
        $this->assertEquals('test_value', $value);
    }

    public function test_it_caches_the_retrieved_value()
    {
        // Arrange
        Configuration::create([
            'key' => 'CACHE_TEST_KEY',
            'value' => 'cached_value',
            'description' => 'Test Cache'
        ]);

        // Act
        $this->service->get('CACHE_TEST_KEY');

        // Assert
        $this->assertTrue(Cache::has('config:CACHE_TEST_KEY'));
        $this->assertEquals('cached_value', Cache::get('config:CACHE_TEST_KEY'));
    }

    public function test_it_retrieves_default_value_if_key_does_not_exist()
    {
        // Act
        $value = $this->service->get('NON_EXISTENT_KEY', 'default_value');

        // Assert
        $this->assertEquals('default_value', $value);
    }

    public function test_it_retrieves_typed_values_correctly()
    {
        // Arrange
        Configuration::create(['key' => 'FLOAT_KEY', 'value' => '10.5', 'description' => 'Float']);
        Configuration::create(['key' => 'INT_KEY', 'value' => '100', 'description' => 'Int']);
        Configuration::create(['key' => 'BOOL_KEY', 'value' => '1', 'description' => 'Bool']);

        // Act & Assert
        $this->assertIsFloat($this->service->getFloat('FLOAT_KEY', 0.0));
        $this->assertEquals(10.5, $this->service->getFloat('FLOAT_KEY', 0.0));

        $this->assertIsInt($this->service->getInt('INT_KEY', 0));
        $this->assertEquals(100, $this->service->getInt('INT_KEY', 0));

        // For boolean, checking loose comparison as DB stores 1/0
        $this->assertTrue((bool)$this->service->get('BOOL_KEY'));
    }

    public function test_cache_is_cleared_when_model_is_updated()
    {
        // Arrange
        $config = Configuration::create([
            'key' => 'UPDATE_KEY',
            'value' => 'old_value',
            'description' => 'Update Test'
        ]);

        // Cache it
        $this->service->get('UPDATE_KEY');
        $this->assertTrue(Cache::has('config:UPDATE_KEY'));

        // Act - Update (Model Model Logic handles cache clearing in boot method)
        $config->update(['value' => 'new_value']);

        // Assert
        $this->assertFalse(Cache::has('config:UPDATE_KEY'), 'Cache should be cleared after update');
        
        // Confirm new value is fetched
        $this->assertEquals('new_value', $this->service->get('UPDATE_KEY'));
    }
}
