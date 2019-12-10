<?php
namespace Pogo;

use Clippy\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {

  public function testConstant() {
    $c = new Container();
    $c['foo'] = 'bar';
    $this->assertEquals('bar', $c['foo']);
  }

  public function testBasicService() {
    $c = new Container();
    $c['foo'] = function () {
      $stdClass = new \stdClass();
      $stdClass->name = 'world';
      return $stdClass;
    };

    $this->assertEquals('world', $c['foo']->name);
  }

  public function testDependentServices() {
    $c = new Container();
    $c['foo'] = function () {
      $stdClass = new \stdClass();
      $stdClass->name = 'world';
      return $stdClass;
    };
    $c['bar'] = function ($foo) {
      $stdClass = new \stdClass();
      $stdClass->name = $foo->name . $foo->name;
      return $stdClass;
    };

    $c['foo']->extra = 123;
    $this->assertEquals('worldworld', $c['bar']->name);
    $this->assertEquals('world', $c['foo']->name);
    $this->assertEquals(123, $c['foo']->extra);
    $this->assertTrue(empty($c['bar']->extra));
  }

  public function testFactoryService() {
    $nextId = 0;

    $c = new Container();
    $c['foo'] = function () {
      $stdClass = new \stdClass();
      $stdClass->name = 'world';
      return $stdClass;
    };
    $c['bar'] = $c->factory(function ($foo) use (&$nextId) {
      $stdClass = new \stdClass();
      $stdClass->name = $foo->name . '_' . $nextId;
      $nextId++;
      return $stdClass;
    });

    $this->assertEquals('world_0', $c['bar']->name);
    $this->assertEquals('world_1', $c['bar']->name);
    $this->assertEquals('world_2', $c['bar']->name);
  }

  public function testFactoryServiceSigil() {
    $nextId = 0;

    $c = new Container();
    $c['foo'] = function () {
      $stdClass = new \stdClass();
      $stdClass->name = 'world';
      return $stdClass;
    };
    $c['bar++'] = function ($foo) use (&$nextId) {
      $stdClass = new \stdClass();
      $stdClass->name = $foo->name . '_' . $nextId;
      $nextId++;
      return $stdClass;
    };

    $this->assertEquals('world_0', $c['bar']->name);
    $this->assertEquals('world_1', $c['bar']->name);
    $this->assertEquals('world_2', $c['bar']->name);
    $this->assertEquals('world_3', $c['bar++']->name);
  }

  public function testServiceMethod() {
    $c = new Container();
    $c['foo'] = function () {
      $stdClass = new \stdClass();
      $stdClass->name = 'world';
      return $stdClass;
    };
    $c['doIt'] = $c->method(function ($count, $foo) {
      $buf = '';
      for ($i = 0; $i < $count; $i++) {
        $buf .= $foo->name;
      }
      return $buf;
    });

    $this->assertEquals('world', $c['doIt'](1));
    $this->assertEquals('worldworldworld', $c['doIt'](3));
    $this->assertEquals('worldworld', $c['doIt'](2));
  }

  public function testServiceMethodSigil() {
    $c = new Container();
    $c['foo'] = function () {
      $stdClass = new \stdClass();
      $stdClass->name = 'world';
      return $stdClass;
    };
    $c['doIt()'] = function ($count, $foo) {
      $buf = '';
      for ($i = 0; $i < $count; $i++) {
        $buf .= $foo->name;
      }
      return $buf;
    };

    $this->assertEquals('world', $c['doIt'](1));
    $this->assertEquals('worldworldworld', $c['doIt()'](3));
    $this->assertEquals('worldworld', $c['doIt'](2));
  }

}
