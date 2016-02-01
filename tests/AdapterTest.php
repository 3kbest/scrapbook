<?php

namespace MatthiasMullie\Scrapbook\Tests;

class AdapterTest extends AdapterTestCase
{

    public function testSetMulti()
    {
        $items = array(
            'test key' => 'value',
            'key2' => 'value2',
        );

        $return = $this->cache->setMulti($items);

        $expect = array_fill_keys(array_keys($items), true);
        $this->assertEquals($expect, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
        $this->assertEquals('value2', $this->cache->get('key2'));
    }

    public function testSetMultiExpired()
    {
        $items = array(
            'test key' => 'value',
            'key2' => 'value2',
        );

        $return = $this->cache->setMulti($items, time() - 2);

        $expect = array_fill_keys(array_keys($items), true);
        $this->assertEquals($expect, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
        $this->assertEquals(false, $this->cache->get('key2'));
    }

    public function testDelete()
    {
        $this->cache->set('test key', 'value');

        $return = $this->cache->delete('test key');

        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));

        // delete non-existing key
        $return = $this->cache->delete('key2');

        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('key2'));
    }

    public function testDeleteMulti()
    {
        $items = array(
            'test key' => 'value',
            'key2' => 'value2',
        );

        $this->cache->setMulti($items);
        $return = $this->cache->deleteMulti(array_keys($items));

        $expect = array_fill_keys(array_keys($items), true);
        $this->assertEquals($expect, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
        $this->assertEquals(false, $this->cache->get('key2'));

        // delete all non-existing key (they've been deleted already)
        $return = $this->cache->deleteMulti(array_keys($items));

        $expect = array_fill_keys(array_keys($items), false);
        $this->assertEquals($expect, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
        $this->assertEquals(false, $this->cache->get('key2'));

        // delete existing & non-existing key
        $this->cache->set('test key', 'value');
        $return = $this->cache->deleteMulti(array_keys($items));

        $expect = array_fill_keys(array_keys($items), false);
        $expect['test key'] = true;
        $this->assertEquals($expect, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
        $this->assertEquals(false, $this->cache->get('key2'));
    }

    public function testAdd()
    {
        $return = $this->cache->add('test key', 'value');

        $this->assertEquals(true, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }

    public function testAddFail()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->add('test key', 'value-2');

        $this->assertEquals(false, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }

    public function testAddExpired()
    {
        $return = $this->cache->add('test key', 'value', time() - 2);

        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testReplace()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->replace('test key', 'value-2');

        $this->assertEquals(true, $return);
        $this->assertEquals('value-2', $this->cache->get('test key'));
    }

    public function testReplaceFail()
    {
        $return = $this->cache->replace('test key', 'value');

        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testReplaceExpired()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->replace('test key', 'value', time() - 2);

        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testReplaceSameValue()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->replace('test key', 'value');

        $this->assertEquals(true, $return);
    }

    public function testCas()
    {
        $this->cache->set('test key', 'value');

        // token via get()
        $this->cache->get('test key', $token);
        $return = $this->cache->cas($token, 'test key', 'updated-value');

        $this->assertEquals(true, $return);
        $this->assertEquals('updated-value', $this->cache->get('test key'));

        // token via getMulti()
        $this->cache->getMulti(array('test key'), $tokens);
        $token = $tokens['test key'];
        $return = $this->cache->cas($token, 'test key', 'updated-value-2');

        $this->assertEquals(true, $return);
        $this->assertEquals('updated-value-2', $this->cache->get('test key'));
    }

    public function testCasFail()
    {
        $this->cache->set('test key', 'value');

        // get CAS token
        $this->cache->get('test key', $token);

        // write something else to the same key in the meantime
        $this->cache->set('test key', 'updated-value');

        // attempt CAS, which should now fail (token no longer valid)
        $return = $this->cache->cas($token, 'test key', 'updated-value-2');

        $this->assertEquals(false, $return);
        $this->assertEquals('updated-value', $this->cache->get('test key'));
    }

    public function testCasFail2()
    {
        $this->cache->set('test key', 'value');

        // get CAS token
        $this->cache->get('test key', $token);

        // delete that key in the meantime
        $this->cache->delete('test key');

        // attempt CAS, which should now fail (token no longer valid)
        $return = $this->cache->cas($token, 'test key', 'updated-value-2');

        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testCasExpired()
    {
        $this->cache->set('test key', 'value');

        // token via get()
        $this->cache->get('test key', $token);
        $return = $this->cache->cas($token, 'test key', 'updated-value', time() - 2);

        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testCasSameValue()
    {
        $this->cache->set('test key', 'value');
        $this->cache->get('test key', $token);
        $return = $this->cache->cas($token, 'test key', 'value');

        $this->assertEquals(true, $return);
    }

    public function testCasNoOriginalValue()
    {
        $this->cache->get('test key', $token);
        $return = $this->cache->cas($token, 'test key', 'value');

        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testIncrement()
    {
        // set initial value
        $return = $this->cache->increment('test key', 1, 1);

        $this->assertEquals(1, $return);
        $this->assertEquals(1, $this->cache->get('test key'));

        $return = $this->cache->increment('key2', 1, 0);

        $this->assertEquals(0, $return);
        $this->assertEquals(0, $this->cache->get('key2'));

        // increment
        $return = $this->cache->increment('test key', 1, 1);

        $this->assertEquals(2, $return);
        $this->assertEquals(2, $this->cache->get('test key'));
    }

    public function testIncrementFail()
    {
        $return = $this->cache->increment('test key', -1, 0);
        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('test key'));

        $return = $this->cache->increment('test key', 5, -2);
        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('test key'));

        // non-numeric value in cache
        $this->cache->set('test key', 'value');
        $return = $this->cache->increment('test key', 1, 1);
        $this->assertEquals(false, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }

    public function testIncrementExpired()
    {
        // set initial value
        $return = $this->cache->increment('test key', 1, 1, time() - 2);

        $this->assertEquals(1, $return);
        $this->assertEquals(false, $this->cache->get('test key'));

        // set initial value (not expired) & increment (expired)
        $this->cache->increment('test key', 1, 1);
        $return = $this->cache->increment('test key', 1, 1, time() - 2);

        $this->assertEquals(2, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testDecrement()
    {
        // set initial value
        $return = $this->cache->decrement('test key', 1, 1);

        $this->assertEquals(1, $return);
        $this->assertEquals(1, $this->cache->get('test key'));

        $return = $this->cache->decrement('key2', 1, 0);

        $this->assertEquals(0, $return);
        $this->assertEquals(0, $this->cache->get('key2'));

        // decrement
        $return = $this->cache->decrement('test key', 1, 1);

        $this->assertEquals(0, $return);
        $this->assertEquals(0, $this->cache->get('test key'));

        // decrement again (can't go below 0)
        $return = $this->cache->decrement('test key', 1, 1);

        $this->assertEquals(0, $return);
        $this->assertEquals(0, $this->cache->get('test key'));
    }

    public function testDecrementFail()
    {
        $return = $this->cache->decrement('test key', -1, 0);
        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('test key'));

        $return = $this->cache->decrement('test key', 5, -2);
        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('test key'));

        // non-numeric value in cache
        $this->cache->set('test key', 'value');
        $return = $this->cache->increment('test key', 1, 1);
        $this->assertEquals(false, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }

    public function testDecrementExpired()
    {
        // set initial value
        $return = $this->cache->decrement('test key', 1, 1, time() - 2);

        $this->assertEquals(1, $return);
        $this->assertEquals(false, $this->cache->get('test key'));

        // set initial value (not expired) & increment (expired)
        $this->cache->decrement('test key', 1, 1);
        $return = $this->cache->decrement('test key', 1, 1, time() - 2);

        $this->assertEquals(0, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testTouch()
    {
        $this->cache->set('test key', 'value');

        // not yet expired
        $return = $this->cache->touch('test key', time() + 2);
        $this->assertEquals(true, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }

    public function testTouchExpired()
    {
        $this->cache->set('test key', 'value');

        // expired
        $return = $this->cache->touch('test key', time() - 2);
        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }

    public function testFlush()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->flush();

        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }
}
