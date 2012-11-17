<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 * @version $Id: Mars_OpenIDAttributeExchange.class.php 2803 2011-06-02 16:36:43Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * OpenID プロバイダが所有するユーザの属性情報を要求・取得するためのコンテナを提供します。
 * OpenID Attribute Exchange が提供する属性については {@link http://www.axschema.org/  OpenID 拡張仕様} を参照して下さい。
 *
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 */

class Mars_OpenIDAttributeExchange extends Mars_Object
{
  /**
   * ユーザ属性情報。
   * @var array
   */
  private $_attributes = array();

  /**
   * コンストラクタ。
   * 
   * @param array $attributes コンテナに登録するユーザ属性。
   *   array('属性のタイプ' => '属性値') の形式で複数登録可能。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function __construct($attributes = array())
  {
    $this->_attributes = $attributes;
  }

  /**
   * OpenID プロバイダに指定した属性の値を要求します。
   * 
   * @param string $name 要求するユーザ属性のタイプ。
   *   例えばユーザ名を要求する場合は、'namePerson/friendly' を指定します。
   * @link http://www.axschema.org/types/ Attribute Types
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function addAttribute($name)
  {
    $this->_attributes[] = $name;
  }

  /**
   * OpenID プロバイダから返却されたユーザ属性を取得します。
   * 
   * @param string $name 取得対象の属性タイプ。
   *   例えばユーザ名を取得したい場合は 'namePerson/friendly' を指定します。
   * @link http://www.axschema.org/types/ Attribute Types
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getAttribute($name)
  {
    return array_find($this->_attributes, $name);
  }

  /**
   * OpenID プロバイダから返却された全てのユーザ属性を取得します。
   * 
   * @return array OpenID プロバイダから返却された全てのユーザ属性を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getAttributes()
  {
    return $this->_attributes;
  }
}
