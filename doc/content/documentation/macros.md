+++
title = "Macros"
weight = 11
+++

Phel supports macros. Macros are function that take code as input and return transformed code as output. A macro is like a function that is executed at compile time. They are useful to extend the syntax of the language itself.

Phel's core library itself uses macro to define the language. For example `defn` is as marco

```phel
(defn add [a b] (+ a b))
```
is transformed to
```phel
(def add (fn [a b] (+ a b)))
```

## Quote

The quote operator is a special form, it returns its argument without evaluating it. Its purpose is to prevent any evaluation. Preceding a form with a single quote is a shorthand for `(quote form)`.

```phel
(quote my-sym) # Evaluates to my-sym
'my-sym # Shorthand for (same as above)
```
Quote make macros possible, since its helps to distinguish between code and data. Literals like numbers and string evaluate to themself.

```phel
(quote 1) # Evaluates to 1
(quote hi) # Evaluates the symbol hi
(quote quote) # Evaluates to the symbol quote

'(1 2 3) # Evaluates to the tuple (1 2 3)
'(print 1 2 3) # Evaluates to the tuple (print 1 2 3). Nothing is printed.
```

# Define a macro

```phel
(defmacro docstring? attributes? [params*] expr*)
```

The `defmacro` function can be used to create a macro. It takes the same parameters as `defn`.

Together with `quote` and `defmarco` it is now possible to define a custom `defn`, which is called `mydefn`:

```phel
(defmacro mydefn [name args & body]
  (tuple 'def name (apply tuple 'fn name args body)))
```
This macro is very simple at does not support all the feature of `defn`. But it illustrates the basics of a macro.

## Quasiquote

For better readability of marcos the `quasiquote` special form is defined. It turns the definion of macros around. Instead of quoting values that should not be evaluated, `quasiquote` marks values that should be evaluates. Every other value is not evaluated. A shorthand for `quasiquote` is the `` ` `` character. Values that should be evaluates are marked with the `unquote` function (shorthand `,`) or `unquote-splicing` function (shorthand `,@`). With quasiquote the `mydefn` macro can be expressed as

```phel
(defmacro mydefn [name args & body]
  `(def ,name (fn ,name ,args ,@body)))