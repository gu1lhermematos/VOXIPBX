*------------------------------------------------------------------------------------
* Opens Tecnologia - Projeto Snep Livre
* Processo para incluir novas strings de traducao no arquivo messages.
* Processo para gerar/atualizar o arquivo .mo da lingua desejada
* Autor: Flavio Henrique Somensi - flaviO@opens.com.br - 2013.
*------------------------------------------------------------------------------------
1) Instalar o gettext
  - para debian: apt-get install gettext
  - para outros SO's: wget http://ftp.gnu.org/pub/gnu/gettext/gettext-0.18.2.tar.gz
    - Descompactar
    - cd gettext-0.18.2
    - make
    - make install
  Manual do gettext: http://www.gnu.org/software/gettext/manual/gettext.html

2) Execute o script shell gettext.sh que está neste diretório
   ==>> IMPORTANTE: execute o programa definindo o caminho da pesquisa
                    Exemplo: ./gettext.sh ../  (procura a partir da rtaiz do SNEP)
   - Este programa irá varrer todos os arquivos .php, .phtml e .xml a procura
     de strings do tipo "translate"
   - Ao final irá gerar um arquivo chamado messages.po

3) Utilize um programa do tipo Poedit ou similar para gerar as traduções na 
   linguagem desejada.
   Exemplo: Usando o Poedit
   a) Abra o arquivo .po desejado (Ex: snep2-pt_BR.po)
   b) No menu Catálogo, acesse a opção: Atualizar com base em ficheiro POT
      -> Selecione o arquivo:  messages.po
   c) Ao final, ao salvar, será gerado um arquivo snep2-pt_BR.mo

4) Cada módulo do SNEP e o próprio SNEP tem seus arquivos de traduçào separados.
   Os arquivos utilizados pela interface estão em langs/_LOCALE_/ , onde:
   _LOCALE_ = sigla da lingua (pt_BR, es, en, etc)

5) Copie arquivo .mo salvo pelo programa de tradução (Ex. snep2-pt_BR.mo) para o 
   diretório específico (Ex: /langs/pt_BR/snep2-pt_BR.mo) 
   ==>> IMPORTANTE: Note que cada módulo tem seu próprio arquivo de traducão
                    Note que o SNEP tem seu próprio arquivo de tradução

                    Use a seguinte notação: nome_modulo-LOCALE.mo
                    Exemplo: Para o SNEP           - snep2-pt_BR.mo
                             Para o modulo Agentes - agentes-pt_BR.mo
                             Para o modulo CC      - cc-pt_BR.mo

6) Dúvidas: contato@snelivre.com.br 
