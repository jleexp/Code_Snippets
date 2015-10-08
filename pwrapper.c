#include <stdio.h>
#include <stdlib.h>
#include <string.h>

int main(int argc, char* argv[]) {
  int i;
  char arg[255];
  char cmd[1024];

  setuid(geteuid());

  if ( argc>1 ) {
    sprintf(cmd,"/usr/bin/php -q ");
    for (i=1;i<argc;i++) { 
      sprintf(arg,"%s ",argv[i]);
      strcat(cmd,arg);
    }
    system(cmd);
  }
}
