<?php
namespace AsyncWeb\DefaultBlocks;


class Header extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		$this->template = '<header class="navbar navbar-default navbar-inverse" id="top" role="banner">
  <div class="container">
    <div class="navbar-header">
      <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target=".bs-navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a href="/" class="logo"><i style="font-size:40px; margin:3px 20px 3px 0" class="fa fa-rocket"></i></a>
    </div>
    <nav class="collapse navbar-collapse bs-navbar-collapse">
	{{{TopMenu}}}
	{{{LangMenu}}}
    </nav>
  </div>
</header>';
	}
}