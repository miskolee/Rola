<?php
//error_reporting(0);
    include 'rola.php';
    //第三方DOM解析库
    include 'include/simple_html_dom.php';

    /*** URL分离重新生成规范URL。提供健壮的保证URL合法性的代码
     ** @return $new['url'] 目录
     ** @return $new['base'] 域名*********/ 
    function check_url($url,$base=''){      
        $pattern="/((?:http:\/\/)?(?:www\.)?[^\/]*)(.*)/i";
          $match=null;
          $new=array();
          preg_match($pattern,$url,$match);
          if($match[1] && $match[1][0]!='.'){
                $new['base']=$match[1];
          
          }else{  
              $base && $new['base']=$base;
          }
          if(stripos("http://",$new['base'])){
                $new['base'].="http://".$new['base'];
          };
          /**重新解析的URL******/
          $new['url']=$new['base'].$match[2];
          //echo "url".$this->url;
          if($match[2][0]!='/')   
          $new['url']=$new['base']."/".$match[2];
        return $new;
        }


    /**** 匹配一个HTML页面  
     ***  使用流程，在对象构造中,使用一个URL链接以及一个
     ***  rola语句定义匹配规则
     ***  例子：$kobe=new Kobe("http://127.0.0.1",'<div<div>>');
     ***  //匹配结构成功后才可以使用$this->rola获取内容集
     ***  //自定义一个用于在$this->rola中搜索的回调函数
     ***  
     *** if($kobe->rola){$kobe->callback(new function());}**/
    class Kobe{
        public function __construct($url,$filter){
          
           $this->domain= check_url($url);
           $this->url=$this->domain['url'];
           $this->base=$this->url['base'];
          $this->stats=true; 
          $this->filter=$filter;
          $this->init();
        }
        private function init(){
            $this->dom=file_get_html($this->url);
            $this->curosr=$this->dom;
            $this->filter($this->filter);
            $this->slolver($this->slolver_root(),$this->rola->children,$this->stats);
        if(!$this->stats){
            $this->rola=null;   
         }
        }

        /***@param $partten calss Rola***/
        public function filter($partten){
            $this->rola=new Rola($partten);
            $this->rola=$this->rola->root;
        }
        /*获取初始化定界元素*****/
        public function slolver_root(){
            //echo $this->rola->children[0]->toKey();
            $nodes=$this->dom->root->find($this->rola->children[0]->toKey());
            $this->nodes=$nodes;
            return $nodes;
        }

        /***核心函数 对文档进行匹配提取 ****/
        public function slolver($nodes,$cursor,&$stats){
                $count=0;
                while($stats){
                    if($cursor[$count]->needs){
                        foreach($cursor[$count]->needs as $k=>$v){
                            $arr["$v"]=$nodes[$count]->$v;
                        }
                     $cursor[$count]->data($arr);  
                    }
                    /*** 判断rola文档与实际DOM文档是否相等***/
                $stats=equal($nodes[$count],$cursor[$count]);
                    if($count>count($cursor[$count]->children)){
                        $stats=true;
                    break; 
                    };
                    if($cursor[$count]->children[0] && $nodes[$count]->children[0]){
                       $this->slolver($nodes[$count]->children,$cursor[$count]->children,$stats); 
                    }
                $count++;
                }
        }

        public function __get($key){
                return $this->$key;
        
        }
        public function callback($function){
            return $function($this->rola);
        }
    public  $base;
    public  $url;
    public  $data;
    private $domain;
    private $dom;
    private $cursor;
    public  $rola;
    public  $stats;
    public  $filter;
    public  $callback;
    public  $nodes;
    }
/*** class id tagName ****/
function equal($node,$treeNode){
    if($treeNode->tag!=$node->tag) 
        return false;
    if($treeNode->id){
        echo "id error";
        echo $treeNode->id;
        if($node->id !=$treeNode->id){
            return false;
        }
    }
    if($treeNode->class){
        if($node->class !=$treeNode->class){
            return false;
        }
    }
    return true;
}

   /**********************   TEST ****************************
    
        $a="a<div(class=categories-list){id%class}<ul()<li()<span()<a(){href}>>>>>a";
        $host="http://127.0.0.1/jomal";
    $kobe=new Kobe($host,$a);    
    var_dump($kobe->stats);
    $rola=$kobe->rola;
    $stats=true;
    //var_dump($rola);
    
    //内容提取callback函数 ****
function formata($tree){
        if($tree->children){
        foreach($tree->children as $rola){
        if(!$rola->tag)        
             $stats=false;
        if($rola->data){
            var_dump($rola->data);    
        }
        if($rola->children){
            formata($rola->children);
        }
    }
    }
}
formata($rola);
*/
?>
