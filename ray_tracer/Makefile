#!gmake

#-----------------------------------------
#Basic Stuff -----------------------------
CC          = g++
cc          = gcc

#-----------------------------------------
#Optimization ----------------------------
OPT   = -O3 

#-----------------------------------------
# X       --------------------------------

X_LIB  =  -lXm -lXi -lX11 -L/usr/X11R6/lib 

#-----------------------------------------
# GL      --------------------------------

GL_LIB = -lGL -lglut -lGLU


#-----------------------------------------
#-----------------------------------------

TARGETS = main
OBJECTS = main.o smVector.o framework.o
EXENAME = raytrace

#-----------------------------------------

# LIBS = $(X_LIB) $(GL_LIB) -lm
# INCS = -I/usr/X11R6/include 

LIBS =
INCS =

CCOPTS = $(OPT) $(DEBUG) $(INCS) 
LDOPTS = $(OPT) $(DEBUG) $(INCS) 

#-----------------------------------------
#-----------------------------------------

default: $(TARGETS)


clean: 
	/bin/rm -f *.o $(EXENAME) $(EXENAME).exe

#-----------------------------------------
#-----------------------------------------

main: $(OBJECTS)
	g++ $(OPT) -o $(EXENAME) $(OBJECTS) $(LIBS) 

#-----------------------------------------

.cpp.o: 
	$(CC) $(CCOPTS) -c $< -o $@

.C.o: 
	$(CC) $(CCOPTS) -c $< -o $@

.c.o: 
	$(cc) $(CCOPTS) -c $< -o $@

.o: $(OBJECTS)
	$(CC) $(LDOPTS) $(OBJS) $(OBJECTS) $< $(LIBS) -o $@

.C: $(OBJECTS)
	$(CC) $(LDOPTS) $(OBJS) $(OBJECTS) $< $(LIBS) $(FOR_LIB) -o $@

#-----------------------------------------
#-----------------------------------------

