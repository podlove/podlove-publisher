<?php
/*! This file implements several classes to calculate and display a linear
* regression to compare episodes to each other. The regression is calculated
* based on the download stats of each episode after 2 weeks.
*
* To use it, copy this file into the podlove-publisher directory into the
* folder lib/settings.
* Insert the following code into the file analytics.php, e.g. after the
* downloads chart, approx. at line number 305:
* <?php require("regression.php"); new PodloveRegression(array()); ?>
*
* @auther Bernhard R. Fischer, <bf@abenteuerland.at>
* @date 2019/12/05
* @version 1.3
 */

namespace Podlove\Settings;

use \Podlove\Model;


/*! The class Regression is a class to calculate a linear regression of an
   * 1-dimensional array of numbers.
 */
class Regression
{
   //! internal calculation state, new
   const REG_NEW = 0;
   //! internal calculation state, regression parameters ready
   const REG_PARAM = 1;
   //! internal calculation state, regression ready
   const REG_CALC = 2;
   //! variable to keep internal state of calculation
   protected $calc_state = Regression::REG_NEW;

   //! maximum download value
   protected $max_dn_ = 0;
   //! number of elements in array
   protected $cnt_ = 0;
   //! data array
   protected $dat_ = array();
   //! regression parameter b0
   protected $b0_ = 0;
   //! regression parameter b1
   protected $b1_ = 0;
   //! determination
   protected $r2_ = 0;
   //! max regression value
   protected $max_reg_ = 0;


   /*! Object constructur.
    * @param $dat 1-dimensional array of numbers.
    */
   function __construct($dat)
   {
      $this->max_dn_ = max($dat);
      $this->cnt_ = count($dat);
      $this->dat_ = array('val' => $dat);
   }


   /*! This method calculates the regression parameters b0 and b1.
    */
   function reg_calc_param()
   {
      $sum = array_sum($this->dat_['val']);
      $xsum = $this->cnt_ * ($this->cnt_ - 1) / 2;

      $avg_ep = $xsum / $this->cnt_;
      $avg_dn = $sum / $this->cnt_;

      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $this->dat_['xx'][$i] = $i - $avg_ep;
         $this->dat_['yy'][$i] = $this->dat_['xy'][$i] = $this->dat_['val'][$i] - $avg_dn;
         $this->dat_['xy'][$i] *= $this->dat_['xx'][$i];
         $this->dat_['xx'][$i] *= $this->dat_['xx'][$i];
         $this->dat_['yy'][$i] *= $this->dat_['yy'][$i];
      }

      $xy_sum = array_sum($this->dat_['xy']);
      $xx_sq = array_sum($this->dat_['xx']);

      $this->b1_ = $xy_sum / $xx_sq;
      $this->b0_ = $avg_dn - $this->b1_ * $avg_ep;

      $this->calc_state = Regression::REG_PARAM;
   }


   /*! This method calculates the regression values to each point based on the
     * regression parameters b0 and b1.
     * The method automatically calls reg_calc_param() if it was not called
     * before.
    */
   function reg_calc()
   {
      if ($this->calc_state == Regression::REG_NEW)
         $this->reg_calc_param();

      $this->dat_['rval'] = array();
      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $this->dat_['rval'][$i] = $this->b0_ + $this->b1_ * $i;
         $this->dat_['dy2'][$i] = $this->dat_['dev'][$i] = $this->dat_['val'][$i] - $this->dat_['rval'][$i];
         $this->dat_['dy2'][$i] *= $this->dat_['dy2'][$i];
         if ($this->dat_['rval'][$i])
            $this->dat_['p'][$i] = $this->dat_['dev'][$i] / $this->dat_['rval'][$i];
      }

      $yy_sum = array_sum($this->dat_['yy']);
      $dy2_sum = array_sum($this->dat_['dy2']);
      if ($yy_sum > 0)
         $this->r2_ = 1 - $dy2_sum / $yy_sum;

      $this->max_reg_ = max($this->dat_['rval']);
      $this->calc_state = Regression::REG_CALC;
   }


   /*! This method returns the array of regression values (y-values to each
      * point). The function automatically calls reg_calc() if it wasn't called
      * already.
      * @return Returns an array of values.
    */
   function rval()
   {
      if ($this->calc_state != Regression::REG_CALC)
         $this->reg_calc();

      return $this->dat_['rval'];
   }


   /*! This method returns the array of internal values. This is the same as
      * was initialized with the constructor.
      * @return Returns an array of values.
    */
   function val()
   {
      return $this->dat_['val'];
   }


   /*! This function returns an array with deviation values. This is the
      * difference between the regression value and the actual value.
      * @return Returns an array of values.
    */
   function dev()
   {
      return $this->dat_['dev'];
   }


   /*! This function returns an array with deviation in percentage. This is how
      * many percent is the deviation of the regression.
    */
   function p()
   {
      return $this->dat_['p'];
   }


   /*! This method returns the element count, i.e. how many array elements are
    * there.
    * @return Number of elements in the array.
    */
   function count()
   {
      return $this->cnt_;
   }


   /*! This method returns the maximum value of both arrays, the original
      * values and the regression values.
      * @return Maximum y value on y-axis.
    */
   function max()
   {
      return max($this->max_dn_, $this->max_reg_);
   }


   /*! This function returns the regression parameters as an array.
      * yi = b0 + b1 * xi
      * @return Associative array with elements 'b0', 'b1', and 'r2'.
    */
   function rparam()
   {
      return array('b0' => $this->b0_, 'b1' => $this->b1_, 'r2' => $this->r2_);
   }
}


/*! This class is a helper class the generate JS Canvas code from PHP.
 */
class JSCanvas
{
   protected $id_;
   protected $width_;
   protected $height_;

   protected $script_;


   function __construct($id, $width = 800, $height = 200)
   {
      $this->id_ = $id;
      $this->width_ = $width;
      $this->height_ = $height;
   }


   function output_canvas_tag()
   {
      echo("<div style=\"position:relative;width:$this->width_;height:$this->height_;\">\n");
      echo("<canvas id=\"$this->id_\" width=\"$this->width_\" height=\"$this->height_\"></canvas>\n");
      echo("<canvas id=\"{$this->id_}_tip\" width=\"100\" height=\"25\" style=\"background-color:white;border:1px solid blue;position:absolute;left:-200px;top:100px;\"></canvas>\n");
      echo("</div>");
   }


   function output_script()
   {
      echo($this->script_);
   }


   function start_script()
   {
      $this->script_ .= "<script>\n";
   }

   function start_draw()
   {
      $this->script_ .= "$this->id_();\nfunction $this->id_()\n{\nvar canvas = document.getElementById('$this->id_');\nif (canvas.getContext)\n{\nvar context = canvas.getContext('2d');\n";
   }


   function end_draw()
   {
      $this->script_ .= "}\n}\n";
   }


   function end_script()
   {
      $this->script_ .= "</script>\n";
   }


   function add_tooltip($a)
   {
      $this->script_ .= "tips.push({x1: {$a['x1']}, y1: {$a['y1']}, x2: {$a['x2']}, y2: {$a['y2']}, t: \"{$a['t']}\"});\n";
   }


   function tooltip_code()
   {
      $this->script_ .=
      "
      var canvas = document.getElementById('$this->id_');
      var tip = document.getElementById('{$this->id_}_tip');
      var tips = [];
      var canvasCtx = canvas.getContext('2d');
      var tipCtx = tip.getContext('2d');

      canvas.addEventListener('mousemove', function(e){handleMouseMove(e);});

      function handleMouseMove(e)
      {
         var rect = canvas.getBoundingClientRect();
         mouseX = e.clientX - rect.left;
         mouseY = e.clientY - rect.top;
         var hit = false;
         for (var i = 0; i < tips.length; i++)
         {
            var dot = tips[i];
            if (mouseX >= dot.x1 && mouseY >= dot.y1 && mouseX <= dot.x2 && mouseY <= dot.y2)
            {
               var tx = dot.t;
               tip.style.left = dot.x1 + 10 + 'px';
               tip.style.top = dot.y1 + 10 + 'px';
               tip.width = tipCtx.measureText(tx).width + 10;
               tipCtx.clearRect(0, 0, tip.width, tip.height);
               tipCtx.fillText(tx, 5, 15);
               hit = true;
            }
         }
         if (!hit) { tip.style.left = '-400px'; }
       }
      ";
   }


   function beginPath()
   {
      $this->script_ .= "context.beginPath();\n";
   }


   function moveTo($x, $y)
   {
      $this->script_ .= "context.moveTo($x, $y);\n";
   }


   function lineTo($x, $y)
   {
      $this->script_ .= "context.lineTo($x, $y);\n";
   }


   function stroke()
   {
      $this->script_ .= "context.stroke();\n";
   }


   function fill()
   {
      $this->script_ .= "context.fill();\n";
   }


   function rect($x1, $y1, $x2, $y2)
   {
      $this->beginPath();
      $this->script_ .= "context.rect($x1, $y1, $x2, $y2);\n";
   }


   function strokeStyle($s)
   {
      $this->script_ .= "context.strokeStyle = '$s';\n";
   }


   function fillStyle($s)
   {
      $this->script_ .= "context.fillStyle = '$s';\n";
   }


   function lineWidth($w)
   {
      $this->script_ .= "context.lineWidth = $w;\n";
   }


   function fillText($t, $x, $y)
   {
      $this->script_ .= "context.fillText('$t', $x, $y);\n";
   }
   
   
   function setLineDash($a)
   {
      $this->script_ .= "context.setLineDash([";
      for ($i = 0; $i < count($a); $i++)
      {
         if ($i)
            $this->script_ .= ", ";
         $this->script_ .= $a[$i];
      }
      $this->script_ .= "]);\n";
   }
}


/*! This class creates the HTML canvas and the necessary JS code to generate a
   * regression diagram.
   * To use it, first instantiate an object and pass at least a Regression
   * object to the constructur. Then call the method draw() which will
   * internally generate all necessary HTML5 code. Finally call the method
   * output() which will print the full HTML5 code.
 */
class DrawRegression
{
   //! maximum number of dynamic y-axis grid
   const MAX_GRID_LINES = 12;

   //! internal Regression object
   protected $reg_;
   //! descriptional data of elements
   protected $desc_;
   //! JSCanvas object
   protected $can_;

   //! diagram width
   protected $width_;
   //! diagram height
   protected $height_;

   //! first episode (array index) to display on diagram
   protected $start_ = 0;
   //! number of episodes to display
   protected $cnt_;

   //! border pixels (border around diagram within width and height)
   protected $border_ = 40;
   //! width of regression line
   protected $bars_ = 5;
   //! x-scaling factor for bars
   protected $xmul_ = 1;
   //! y-scaling factor for episode downloads
   protected $ymul_ = 1;


   /*! Construct (initialize) DrawRegression object.
      * @param $reg Regression object.
      * @param $width Optional diagram width in pixels.
      * @param $height Optional diagram height in pixels.
      * @param $vis Number of episodes (elements within regression array) to
      *        display. If $vis is 0, all elements are display, otherwise it
      *        displays the last $vis elements.
    */
   function __construct($reg, $width = 800, $height = 200, $vis = 0)
   {
      $this->reg_ = $reg;
      $this->width_ = $width;
      $this->height_ = $height;

      if ($vis <= 0)
      {
         $this->cnt_ = $this->reg_->count();
      }
      else
      {
         $this->cnt_ = $vis;
         $this->start_ = $this->reg_->count() - $vis;
      }

      $this->can_ = new JSCanvas("reg_can", $width, $height);
      $this->set_scale_factors();
   }


   function __destruct()
   {
      unset($this->can_);
   }


   //! Set description array.
   function set_description($a)
   {
      $this->desc_ = $a;
   }


   //! Calculate scaling factor xmul_ and ymul_.
   protected function set_scale_factors()
   {
      if ($this->cnt_)
         $this->xmul_ = ($this->width_ - 2 * $this->border_) / $this->cnt_;
      if ($this->reg_->max())
         $this->ymul_ = ($this->height_ - 2 * $this->border_) / $this->reg_->max();
   }


   //! Set the diagram border.
   function set_border($border)
   {
      $this->border_ = $border;
      $this->set_scale_factors();
   }


   //! Generate regression line.
   function draw_regline()
   {
      $this->can_->beginPath();
      $this->can_->moveTo(round($this->border_ + $this->xmul_), round($this->height_ - $this->border_ - ($this->reg_->rval()[$this->start_] * $this->ymul_)));
      $this->can_->lineTo(round($this->width_ - $this->border_), round($this->height_ - $this->border_ - ($this->reg_->rval()[$this->start_ + $this->cnt_ - 1] * $this->ymul_)));
      $this->can_->strokeStyle("#ffd700b0");
      $this->can_->lineWidth($this->bars_);
      $this->can_->stroke();
   }


   //! Calculate coordinates of an episode bar.
   private function bar_coords($i)
   {
      $c = array();

      $bars = $this->xmul_ * 0.5;
      $c['x1'] = round($this->border_ + ($i + 1) * $this->xmul_ - $bars / 2);
      $c['y1'] = round($this->height_ - $this->border_ - $this->reg_->val()[$i + $this->start_] * $this->ymul_);
      $c['w'] = round($bars);
      $c['h'] = round($this->reg_->val()[$i + $this->start_] * $this->ymul_);
      $c['x2'] = $c['x1'] + $c['w'];
      $c['y2'] = $c['y1'] + $c['h'];

      return $c;
   }


   //! Add data for all tooltips to JS.
   function tooltip_data()
   {
      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $c = $this->bar_coords($i);
         $c['t'] = isset($this->desc_[$i + $this->start_]['title']) ? $this->desc_[$i + $this->start_]['title'] : $i + $this->start_ + 1;
         $this->can_->add_tooltip($c);
      }
   }


   //! Generate episode bars.
   function draw_bars()
   {
      $this->can_->fillStyle("#6a5acde0");
      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $c = $this->bar_coords($i);
         $this->can_->rect($c['x1'], $c['y1'], $c['w'], $c['h']);
         $this->can_->fill();
      }
   }


   //! Generate trend bars of episode.
   function draw_trend()
   {
      $this->can_->fillStyle("#6a5acda0");
      $bars = $this->xmul_ * 0.2;
      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $this->can_->fillStyle($this->reg_->dev()[$i + $this->start_] >= 0 ? "#98fb98" : "#fa8072a0");
         $this->can_->rect(round($this->border_ + ($i + 1) * $this->xmul_ - $bars / 2),
            round($this->height_ - $this->border_ - $this->reg_->dev()[$i + $this->start_] * $this->ymul_),
            round($bars), round($this->reg_->dev()[$i + $this->start_] * $this->ymul_));
         $this->can_->fill();
      }
   }


   //! Generate text of percentage plus/minus of each episode.
   function draw_ptext()
   {
      $this->can_->fillStyle("#202020");
      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $p = round($this->reg_->p()[$i + $this->start_] * 100, 1);
         if (abs($p) >= 9.5)
            $p = round($p);

         $s = $p >= 0 ? "+$p%" : "$p%";
         $this->can_->fillText($s,
            round($this->border_ + ($i + 1) * $this->xmul_) . "-context.measureText('$s').width/2",
            round($this->height_ - $this->border_ - $this->reg_->val()[$i + $this->start_] * $this->ymul_));
      }
   }


   //! Generate text of regression parameters and quality.
   function draw_rtext()
   {
      $fk = sprintf("y = %.1f + %.1f x", round($this->reg_->rparam()['b0'], 1), round($this->reg_->rparam()['b1'], 1));
      $r2 = sprintf("r² = %.2f", round($this->reg_->rparam()['r2'], 2));

      $this->can_->fillStyle("#202020");
      $this->can_->fillText($fk, round($this->border_ + 5), round($this->border_));
      $this->can_->fillText($r2, round($this->border_ + 5), round($this->border_ + 12));
   }


   //! Calculate y-scaling, i.e. dynamic grid. FIXME: should be imroved!
   private function yscale()
   {
      $ys = 5;

      for ($m = $this->reg_->max(); $m / $ys > DrawRegression::MAX_GRID_LINES; $ys *= 2);

      return $ys;
   }


   //! Generate diagram axis, grid, and text.
   function draw_axis()
   {
      $this->can_->strokeStyle("#303030");
      $this->can_->lineWidth(1);

      $this->can_->beginPath();
      $this->can_->moveTo(round($this->border_), $this->height_ - $this->border_);
      $this->can_->lineTo(round($this->width_ - $this->border_ + $this->xmul_ * 0.5), $this->height_ - $this->border_);
      $this->can_->stroke();

      $this->can_->beginPath();
      $this->can_->moveTo(round($this->border_), $this->height_ - $this->border_);
      $this->can_->lineTo(round($this->border_), $this->border_);
      $this->can_->stroke();

      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $ii = $i + 1 + $this->start_;
         $this->can_->fillText($ii, round($this->border_ + ($i + 1) * $this->xmul_) . "-context.measureText('$ii').width/2", round($this->height_ - $this->border_ * 0.5 ));
      }

      $this->can_->strokeStyle("#202020");
      $this->can_->setLineDash(array(8, 2));
      $ys = $this->yscale();
      for ($i = $ys; $i < $this->reg_->max(); $i += $ys)
      {
         $this->can_->beginPath();
         $this->can_->moveTo(round($this->border_), round($this->height_ - $this->border_ - $i * $this->ymul_));
         $this->can_->lineTo(round($this->width_ - $this->border_ + $this->xmul_ * .5), round($this->height_ - $this->border_ - $i * $this->ymul_));
         $this->can_->stroke();

         if ($i < 1000)
            $s = $i;
         else
            $s = round($i / 1000, 1) . "k";

         $this->can_->fillText($s, $this->border_ . "-context.measureText('$s ').width", round($this->height_ - $this->border_ - $i * $this->ymul_));
      }
      $this->can_->setLineDash(array());
   }


   //! Generate complete diagram.
   function draw()
   {

      $this->can_->start_script();

      $this->can_->start_draw();
      $this->draw_axis();
      $this->draw_bars();
      $this->draw_regline();
      $this->draw_trend();
      $this->draw_ptext();
      $this->draw_rtext();
      $this->can_->end_draw();

      $this->can_->tooltip_code();
      $this->tooltip_data();

      $this->can_->end_script();
   }


   //! Output diagram.
   function output()
   {
      $this->can_->output_canvas_tag();
      $this->can_->output_script();
   }
}


/*! This class is Podlove-related and generates the regression of the episodes
   * and outputs the necessery HTML and JS code to be displayed within the
   * podlove analytics page.
 */
class PodloveRegression
{
   //! internal array to keep the downloads of all episodes
   protected $data_ = array();
   //! internal array to keep descriptional data of episodes
   protected $desc_ = array();


   function __construct()
   {
      $this->get_episode_data();
      $this->regression();
   }


   /*! Retrieve the download values of each episode after 2 weeks of
    * publishing.
    */
   function get_episode_data()
   {
      $episodes = Model\Episode::all();
      foreach ( $episodes as $episode )
      {
         $post_id = $episode->post_id;
         $post = get_post($post_id);

         // skip deleted podcasts
         if (!in_array($post->post_status, array('pending', 'draft', 'publish', 'future')))
            continue;

         // skip versions
         if ($post->post_type != 'podcast')
            continue;

         // break loop if there is no data
         if (!($d = get_post_meta($post->ID, '_podlove_downloads_2w', true)))
            break;

         $this->data_[] = $d;
         $this->desc_[] = array('title' => $post->post_title);
      }
   }


   /*! Output regression code.
    */
   function regression()
   {
      // initializing HTML code
      ?>
      <div class="metabox-holder">
      <div class="postbox">
      <h2 class="hndle" style="cursor: inherit;">Regression</h2>
      <div class="inside">
      <?php

      // calculate regression
      $reg = new Regression($this->data_);
      $reg->reg_calc();

      // generate regression HTML/JS code
      $dctx = new DrawRegression($reg, 1024, 256, 25);
      $dctx->set_description($this->desc_);
      $dctx->draw();
      // output code
      $dctx->output();
 
      // HTML closing tags
      ?>
      </div>
      </div>
      </div>
      <?php
   }
}


class TestRegression
{
/*   protected $data_ = array(52, 88, 90, 94, 146, 190, 174, 130, 230, 270, 136,
      116, 714, 406, 224, 260, 376, 366, 258, 374, 328, 492, 508, 528, 870,
      576, 716, 494, 528, 446, 406, 518, 494, 584, 484, 612);
 */
   protected $data_ = array(3, 5, 7, 9, 11, 13, 15, 19);


   function __construct()
   {
      // calculate regression
      $reg = new Regression($this->data_);
      $reg->reg_calc();

      // generate regression HTML/JS code
      $dctx = new DrawRegression($reg, 1024, 256, 25);
      $dctx->draw();

      ?><!DOCTYPE html>
      <html>
      <head> <meta charset=utf-8 /> </head>
      <body>
      <?php $dctx->output(); ?>
      <pre>
      <?php print_r($reg); ?>
      </pre>
      </body>
      </html>
      <?php

   }
}

?>
