<?php
/********************************************************************
 **** RoLa是一个简单的元模板生成语言。
 **** 元模板：在HTML文档中提供标签匹配内容搜索
 **** 基于DOM思想而非正则表达式
 **** 主要用于网络爬虫的特定爬取以及网页模板的快速生成
 **** 互联网搜索引擎是基于内容搜索，而RoLa是基于HTML的DOM结构，准确的
 **** 提取莫一部分特定的内容
 **** 本语言不单独使用，只是简单的描述了定义的DOM文档，具体的文档解析
 **** 请使用其他HTML解析工具，如 simple_html_dom 等非常优秀的工具
 **** simple_html_dom是rola的内部使用的DOM处理工具，如果需要使用其他
 **** 工具，需要修改kobe中的代码。未来版本将支持与DOM处理类完全分离
 ===================================================================
 **** @author misko_lee 
 **** @created 2012-11-01
 **** @version 0.12
 **** @email misko_lee@hotmail.com
 *******************************************************************/

/*************************** 语法说明 ******************************
 ===================================================================
 ** 1.所有的标签使用<>包含
 ** 2.所有的属性使用健值对的方式包含在括号中，就算没有属性也必须使用
 **（）作为占位符
 ** 3.多个属性使用&隔开
 ** 4.同级标签使用,分隔
 ** 5.子节点在标签内部直接包含
 ** 6.标签必须有结束符号>
 ** 7.不得使用空格
 ** 8.需要搜索的属性使用{}括号
 ** 下一个版本将支持内嵌正则表达式
 ===================================================================
 ** 例子：
 ** <div(id=nav&class=goods)>
 ** <div(id=nav&class=goods)<p()>,<a(href=http://www.*>
 ** (暂不支持，未来版本加入)
 ** <div(id=id)<a(){href%class}>>
 ===================================================================
 ** 使用例子暂无
 ** $partten="<div(id=id&class=class)>";
 ** $rola=new Rola($partten);
 ** $rola->slolver(); //调用解析器
 ** $rola->toTree(); //获取基于多叉树描述的DOM
 ********************************************************************/

/***节点类,数组描述的多叉树***/
class node{
    public function __construct($parent,$tag='',$attributes=''){
        $this->tag=$tag;
        $this->parent=$parent;
        $this->attributes=array();
        $this->children=array();
        $this->needs=array();
    }
    /*** 向当前指针下添加元素 ***/
    public function push($node){
        $this->children[$this->cursor]=$node;
        $this->cursor++;
    }
    /***搜索的标签 ****/
    public function addAttribute($key,$value){
        $this->attributes["$key"]=$value;
    }

    public function __set($key,$value){
        $this->$key=$value;
    
    }
    /**匹配 ***/
    public function finds($find){
        $this->needs[]=$find;
    
    }
    public function __get($key){
        if(isset($this->$key)){
            return $this->$key;
        }
        if(array_key_exists($key,$this->attributes)){
            return $this->attributes["$key"];
        }
    return false; 
    } 
    /*** 匹配的结果 *****/
    public function data($arr){
        $this->data=$arr;
    
    }
    public function getdata(){
        return $this->data;
    }
    /*** 生成用于DOM匹配的文本 
        优先级，id>class>tagName
        （暂时只支持单标签）
     ***/
   function toKey(){
    $tag=$this->tag;
    array_key_exists('class',$this->attributes) && $tag='.'.$this->attributes['class'];
    array_key_exists('id',$this->attributes) && $tag='#'.$this->attributes['id'];
    return $tag;
   }
private $data;
private $tag;
private $attributes;
public $needs;
private $children;
private $cursor=0;
private $parent;
}


/****解析器类 *******/
class Rola{
   public function __construct($partten){
    $this->partten=$partten;
    $this->root=new node(null,"",null);
    $this->cursor=$this->root;
    $this->slolver();
   }
   
   /**解析器 必须严格遵从语法规定:*****/
   public function slolver(){
       $p=$this->partten;
       $stats=false; //匹配模式开关
       $start=0;
       $end=0;
       $type=null;
       $node=null;
       for($i=0;$i<strlen($p);$i++){
           /*
            
        */
            /**严格的使用关键字符定界***/
            switch($p[$i]){
                /**标签头***/
                case "<":{
                         $stats=true;
                         $start=$i+1;
                         $type='tag';
                         $attr=null;
                         $node=new node($this->cursor);
                         $this->cursor->push($node);
                         if($p[$i-1]!=','){
                            $this->cursor=$node;
                            //var_dump($this->cursor);
                         }
                         }break;
                /**** 标签名****/
                case "(":{
                         $end=$i;
                          $node->tag=substr($p,$start,$end-$start);
                         $stats=true;
                         $type='attrstart';
                         $start=$i+1;
                         }break;
                /**** 属性名****/
                case '=':{
                                $end=$i;
                                $attr=substr($p,$start,$end-$start);
                                $start=$end+1;
                         }break;
                /**** 属性值 ****/
                case '&':{
                             $end=$i;
                                $value=substr($p,$start,$end-$start);
                                $node->addAttribute($attr,$value);
                                $attr=null;
                                $value=null;
                                $start=$i+1;
                         }break;
                case ')':{
                             $end=$i;
                                $value=substr($p,$start,$end-$start);
                                if($p[$i-1]!='(')
                                $node->addAttribute($attr,$value);
                                $attr=null;
                                $value=null;
                                $start=$i+1; 
                         }break;
                case '{':{   
                         $start=$i+1;
                         
                         
                         }break;
                case '}':{
                         $end=$i;
                         $node->finds(substr($p,$start,$end-$start));
                         }break;
                case '%':{
                         $end=$i;
                         $node->finds(substr($p,$start,$end-$start));
                        $start=$i+1; 
                         }break;
                case '>':{  
                            /*指针回溯*****/
                             if($p[$i+1]!=','){
                             $this->cursor=$this->cursor->parent;
                             }
                         }break;
            }
        }
   }
   /**返回树****/
   public function toTree(){
   return  $this->root;
   }
   /**** Debug ************/
   public function format(){
    $this->draw($this->root);
   }
   public function draw($cursor){
        foreach($cursor->children as $c){
            if($c->children){
                 $this->draw($c);           
            }
           // var_dump($c);    
            echo "--------------<br /><br />";
        }
   }
   public function toKey(){
    $key=$this->tag;
    $this->class && $key=$this->class;
    $this->id && $key=$this->id;
    return $key;
   }


   private $cursor; //当前操作指针
   public $root;   //文档树 
}
?>
